<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DashboardWidgetType;
use App\Models\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use IcehouseVentures\LaravelChartjs\Facades\Chartjs;

class DashboardController extends Controller
{
    public function show_dashboard($id)
    {
        ob_start('ob_gzhandler');
        $userId = Auth::id();

        $my_files = FileUpload::select('filename')->where('user_id', $userId)->get();
        $geojson_array = [];
        foreach ($my_files as $my_file) {
            $file = $my_file['filename'];
            $geojson_array[] = ['filename' => preg_replace('/[^A-Za-z0-9\_\.]/', '', basename($file))];
        }

        $dashboard_info = Dashboard::where('user_id', $userId)
            ->where('id', $id)
            ->get();

        $get_widgets = DashboardWidget::where('dashboard_id', $id)->get();

        foreach ($get_widgets as $get_widget) {
            $chart = [];
            $values = [];
            $values_md = [];
            $labels = [];

            $decode_metadata = json_decode($get_widget['metadata'], true) ?: [];
            $get_map_filename = $decode_metadata['map_filename'] ?? null;

            if ($get_widget['widget_type_id'] == 1) {
                if ($get_map_filename) {
                    $get_widget['map_json'] = Storage::get("users/{$userId}/{$get_map_filename}");
                    $get_widget['random_id'] = $get_widget['id'];
                    $get_widget['filename']  = preg_replace('/[^A-Za-z0-9\_\.]/', '', basename($get_map_filename));
                }
                if(array_key_exists('importColors',$decode_metadata) && $decode_metadata['importColors'])
                    $get_widget['importColor'] = true;
                else
                    $get_widget['importColor'] = false;
            }
            elseif ($get_widget['widget_type_id'] == 5) { # TABLE!
                $geojson = FileUpload::select('geojson')
                    ->where('user_id', '=', Auth::id())
                    ->where('filename', '=', $get_map_filename)
                    ->value('geojson');

                $json_version = json_decode($geojson, true);
                if (!is_array($json_version) || ($json_version['type'] ?? '') !== 'FeatureCollection') {
                    // fallback: empty table
                    $get_widget['random_id'] = Str::random();
                    $get_widget['table'] = [];
                    $get_widget['table_headings'] = [];
                } else {
                    // Use the order the user picked in the metadata
                    $picked = $decode_metadata['table_columns'] ?? [];
                    $table_keys = array_values(array_filter($picked, fn($k) => is_string($k) && $k !== ''));

                    // If user somehow saved none, derive from first feature
                    if (empty($table_keys) && !empty($json_version['features'][0]['properties'])) {
                        $table_keys = array_keys($json_version['features'][0]['properties']);
                    }

                    // Build rows deterministically in the same order as $table_keys
                    $value_md = [];
                    foreach ($json_version['features'] as $feature) {
                        $props = $feature['properties'] ?? [];
                        $row = [];
                        foreach ($table_keys as $k) {
                            $row[] = array_key_exists($k, $props) ? $props[$k] : '';
                        }
                        $value_md[] = $row;
                    }

                    $get_widget['random_id'] = Str::random();
                    $get_widget['table'] = $value_md;
                    $get_widget['table_headings'] = $table_keys;
                    $get_widget['visible_columns'] = $decode_metadata['visible_columns'] ?? $table_keys;
                }
            }
            elseif ($get_widget['widget_type_id'] > 1 && $get_widget['widget_type_id'] <= 4) { // CHARTS
                if ($get_map_filename) {
                    $geojson = FileUpload::where('filename', $get_map_filename)
                        ->where('user_id', $userId)
                        ->value('geojson');

                    $json_version = json_decode($geojson ?? '[]', true);

                    // replacing the calculation bits with a call to a function to centralize it all
                    if (!array_key_exists('norm', $decode_metadata))// backwards compatibility...
                        $decode_metadata['norm'] = 'NOPE';
                    if($decode_metadata['norm'] == 'NOPE'){
                        $results = $this->calculateChartData($decode_metadata['x_axis'], $decode_metadata['y_axis'], $json_version['features'], $get_widget['widget_type_id']);
                    }
                    else{
                        $results = $this->compressDatasets($decode_metadata['norm_axis'], $decode_metadata['norm_calc'], $json_version['features'], 0);
                    }
                    $labels = $results['labels'];
                    $values = $results['values'];
                    $categoryWarning = $results['catWarning'];
                    unset($results);// I dunno, feels nice

                    $chart_types    = [2 => 'line', 3 => 'bar', 4 => 'pie', 5 => 'table'];
                    $label_location = ($get_widget['widget_type_id'] == 4) ? 'right' : 'top';
                    $colorMap       = $this->getColorArray($get_widget, $labels);

                    $chart = Chartjs::build()
                        ->name("Chart".Str::random())
                        ->type($chart_types[$get_widget['widget_type_id']])
                        ->size(['width' => 400, 'height' => 200])
                        ->labels($labels)
                        ->datasets([[
                            "label"        => $decode_metadata['x_axis'] ?? '',
                            "data"         => $values,
                            "fill"         => true,
                            "pointRadius"  => 0,
                            "borderWidth"  => 1,
                            "backgroundColor" => $colorMap,
                        ]])
                        ->options([
                            "interaction" => ["mode" => "nearest", "intersect" => false],
                            "hover"       => ["mode" => "nearest", "intersect" => false],
                            "plugins"     => ["legend" => ["position" => $label_location]],
                            "responsive"  => true,
                            "maintainAspectRatio" => false,
                        ]);

                    $get_widget['chart']        = $chart;
                    $get_widget['map_link_id']  = $decode_metadata['mapLinkID'] ?? 'noLink321π'; // <-- expose to Blade
                    $get_widget['category_warning'] = $categoryWarning; // optional: attach flag if you ever want to expose it in Blade
                }
            }

        }

        $get_widget_types = DashboardWidgetType::get();
        $array = [
            'dashboard_info' => $dashboard_info[0],
            'widgets'        => $get_widgets,
            'widget_types'   => $get_widget_types,
            'all_geojsons'   => $geojson_array
        ];

        return view('profile.dashboard', $array);
    }
    private function compressDatasets($inputs, $calculation, $dataset, $toJSON){
        if(!is_array($inputs) || !is_array($dataset) || !is_string($calculation))
            return ($returnArr = ['us' => 'fucked']);

        /*
            $inputs is an array of 'y-axis' or of values to compress
            $calculation is what operation we want to use to compress the dataset
            $dataset is the assoc array of the json, so what we get when decoding the json

            output is an assoc array in the format for graphing, with a tuple for each $input
                $output[$inputs] = $values;
            if $toJSON is set to not zero then we map it to the format of
                $output[$inputs]['properties']['condensed'.$toJSON] = $values
            which keeps it in a 'json' format if we wanna be freaky with it and also keeps track of what value $toJSON is, so we can distinguish between compressed sets if we want to... I dunno, I'm kinda future proofing this with flawed foresight

            operation list: 
            "average"   - average of all non-Null values
            "summation" - summation of all non-Null values
            "count"     - count of all non-Null values
            "min"       - minimum of all non-Null values
            "max"       - maximum of all non-Null values
            (NOT FUNCTIONING PROPERLY)"median"    - median of all non-Null values
            "presence"  - percentage of tuples with that column (nonNulls / null+nonNulls)
            // kinda just felt like putting presence in there tbh, might be useful or something

            mildly sorry for the text dump besties <3

            // max & min needs to be tested and fixed properly
        */
        $newDataset = array();

        $finalAns = 0;
        if($calculation == "average")
            foreach($inputs as $key){
                // for each of the collumns
                $count = 0;
                $average = 0;
                foreach($dataset as $tuple){
                    $val = $tuple['properties'][$key] ?? null;
                    if($val === null) continue;
                    $count++;//only increasing count here for accurate averages
                    $average+=$val;
                }
                $newDataset[$key] = (double)($average / $count);
            }
        else if ($calculation == "summation")
            foreach($inputs as $key){
                $sum = 0;
                foreach($dataset as $tuple){
                    $val = $tuple['properties'][$key] ?? null;
                    if($val === null) continue;
                    $sum+=$val;
                }
                $newDataset[$key] = $sum;
            }
        else if ($calculation == "count")
            foreach($inputs as $key){
                $count = 0;
                foreach($dataset as $tuple){
                    $val = $tuple['properties'][$key] ?? null;
                    if($val === null) continue;
                    $count++;
                }
                $newDataset[$key] = $count;
            }
        else if ($calculation == "min")
            foreach($inputs as $key){
                $min = 99999999; // f it, just as long as something is smaller...
                foreach($dataset as $tuple){
                    $val = $tuple['properties'][$key] ?? null;
                    if($val === null) continue;
                    if($val < $min)
                        $min = $val;
                }
                $newDataset[$key] = $min;
            }
        else if ($calculation == "max")
            foreach($inputs as $key){
                $max = -99999999; // f it, just as long as something is larger...
                foreach($dataset as $tuple){
                    $val = $tuple['properties'][$key] ?? null;
                    if($val === null) continue;
                    if($val > $max)
                        $max = $val;
                }
                $newDataset[$key] = $max;
            }
        // median does not work as the datasets are not sorted...
        else if ($calculation == "median") // this one isn't going to look pretty...
            foreach($inputs as $key){
                $count = 0;
                foreach($dataset as $tuple){ // get number of datapoints
                    $val = $tuple['properties'][$key] ?? null;
                    if($val === null) continue;
                    $count++;
                }
                foreach($dataset as $tuple){ // find the "center" datapoint?
                    $val = $tuple['properties'][$key] ?? null;
                    if($val === null) continue;
                    $count -= 2;
                    if($count == 0)
                        $eval = $val;
                    else if($count == -2){
                        $eval = ($eval + $val)/2;
                        break;
                    }
                    else if($count == -1){
                        $eval = $val;
                        break;
                    }
                }
                $newDataset[$key] = $eval;
            }
        else if ($calculation == "presence"){
            foreach($inputs as $key){
                $count = 0;
                $truecount = 0;
                foreach($dataset as $tuple){
                    $val = $tuple['properties'][$key] ?? null;
                    $truecount++;
                    if($val === null) continue;
                    $count++;
                }
                $newDataset[$key] = $count / $truecount;
            }
        }
        else
            $newDataset[$key] = 17776;
        
        if($toJSON != 0) {
            $tempSet = array();
            foreach ($newDataset as $key => $value)
                $tempSet[$key]['properties'][('condensed'.$toJSON)][$value];
            $newDataset = $tempSet;
        }
        else {
            $compresedResult = [
                'labels' => array_keys($newDataset),
                'values' => array_values($newDataset),
                'catWarning' => false,
            ];
            $newDataset = $compresedResult;
        }
        return $newDataset;
    }

    // $datasets is the assoc array of json btw
    private function calculateChartData($x_axis, $y_axis, $datasets, $widgetTypeId){
        $values_md = [];
        $categoryWarning = false;
        $maxCategories = 100;

        if (($y_axis ?? null) === 'COUNT') {
            foreach (($datasets ?? []) as $feature) {
                $xv = $feature['properties'][$x_axis] ?? null;
                if ($xv === null) continue;
                $values_md[$xv] = ($values_md[$xv] ?? 0) + 1;
            }
        } else {
            foreach (($datasets ?? []) as $feature) {
                // make property names case-insensitive
                $props = array_change_key_case($feature['properties'], CASE_LOWER);
                $xv = $props[strtolower($x_axis)] ?? null;
                $yv = $props[strtolower($y_axis)] ?? null;

                if ($xv === null || !is_numeric($yv)) continue;
                $values_md[$xv] = ($values_md[$xv] ?? 0) + (float)$yv;
            }
        }

        // Cap category count and mark warning
        if (count($values_md) > $maxCategories) {
            $values_md = array_slice($values_md, 0, $maxCategories, true);
            $categoryWarning = true;
        }

        $labels = array_keys($values_md);
        if ($widgetTypeId == 4)
            $labels = array_map(static fn($v) => (string)$v, $labels);
        $values = array_values($values_md);

        $compresedResult = [
            'labels' => $labels,
            'values' => $values,
            'catWarning' => $categoryWarning,
        ];
        return $compresedResult;

    }

    public function add_widgets($id) {
        $dashboard_info = Dashboard::where('user_id', Auth::id())
            ->where('id', $id)
            ->get();
        $my_files = FileUpload::select('filename', 'title')->where('user_id', Auth::id())->get();
        $get_widget_types = DashboardWidgetType::get();

        $widgets = DashboardWidget::where('dashboard_id', $id)->get();
        $mapWidgetList = [];
        foreach ($widgets as $widget) {
            if ($widget->widget_type_id == 1) $mapWidgetList[$widget->id] = $widget->name;
        }

        return view('profile.add-widgets', [
            'dashboard_info' => $dashboard_info[0],
            'widget_types'   => $get_widget_types,
            'files'          => $my_files,
            'mapWidgets'     => $mapWidgetList,
        ]);
    }

    public function add_widget($id, Request $request) {
        $request->validate([
            'widget_type'  => ['required'],
            'map_filename' => ['required'],
        ]);

        if ((int)$request->widget_type === 5) {
            $request->validate([
                'table_columns'   => ['required', 'array', 'min:1'],
                'table_columns.*' => ['string'],
            ]);
        } 
        elseif ((int)$request->widget_type !== 1) {
            $request->validate([
                'mapLinkID' => ['required', 'string'],
            ]);
            if($request->norm_control == "NOPE")
                $request->validate([
                    'x_axis'    => ['required'],
                    'y_axis'    => ['required'],
                    'mapLinkID' => ['required', 'string'],
                ]);
        }

        if($request->norm_control == "YEAH"){ // make an array for our keys
            $norm_axis = $request->norm_axis;
        }

        if (!$request->widget_name) {
            $title = FileUpload::where('user_id', Auth::id())
                ->where('filename', $request->map_filename)
                ->value('title');

            if ((int)$request->widget_type > 1 && (int)$request->widget_type < 5) {
                if($request->norm_control == "NOPE"){
                    $widget_name = ($request->y_axis === 'COUNT')
                        ? "{$request->y_axis} BY {$request->x_axis}"
                        : "SUM OF {$request->y_axis} BY {$request->x_axis}";
                }
                else{
                    $widget_name = "{$request->norm_calc} of ";
                    foreach($norm_axis as $value)
                        $widget_name = $widget_name . " {$value} ";
                }
            } else {
                $widget_name = $title ?? 'Widget';
            }
        } else {
            $widget_name = $request->widget_name;
        }

        $widget = new DashboardWidget;
        $widget->user_id        = auth()->id();
        $widget->dashboard_id   = $id;
        $widget->name           = $widget_name;
        $widget->widget_type_id = (int)$request->widget_type;

        if ((int)$request->widget_type === 1) {
            $metadata = ['map_filename' => $request->map_filename];
        } elseif ((int)$request->widget_type === 5) {
            $metadata = [
                'map_filename'  => $request->map_filename,
                'table_columns' => $request->input('table_columns', []),
            ];
        } else if ($request->norm_control == "YEAH") {
            $metadata = [
                'map_filename'  => $request->map_filename,
                'mapLinkID'     => $request->mapLinkID ?: 'noLink321π',
                'norm'          => $request->norm_control,
                'norm_axis'     => $norm_axis,
                'norm_calc'     => $request->norm_calc,
                'x_axis'       => $request->norm_calc,// makes the map look better, trust
            ];
        } else{
            $metadata = [
                'x_axis'       => $request->x_axis,
                'y_axis'       => $request->y_axis,
                'norm'          => $request->norm_control,
                'map_filename' => $request->map_filename,
                'mapLinkID'    => $request->mapLinkID ?: 'noLink321π',
            ];
        }

        $widget->metadata = json_encode($metadata);
        $widget->save();

        return redirect()->route('profile.dashboard', ['id' => $id]);
    }

    // redirect to the edit widget page. needs the id and the widget id being edited
    public function edit_widgets($dash_id, $id) {

        $dashboard_info = Dashboard::where('user_id', Auth::id())->where('id', $dash_id)->get();
        $widgets = DashboardWidget::where('dashboard_id', $dash_id)->get();
        $my_files = FileUpload::select('filename', 'title')->where('user_id', Auth::id())->get();
        $get_widget_types = DashboardWidgetType::get();
        $mapWidgetList = [];
        foreach ($widgets as $widget) {
            if ($widget->widget_type_id == 1) $mapWidgetList[$widget->id] = $widget->name;
            if ($widget->id == $id) $chosenOne = $widget;
        }

        return view('profile.edit-widgets', [
            'dashboard_info' => $dashboard_info[0],
            'widget_types'   => $get_widget_types,  // sets initial form of edit page
            'mapWidgets'     => $mapWidgetList,     // for if editing a graph widget
            'files'          => $my_files,
            'widget'         => $chosenOne,         // chosen widget to edit
        ]);
    }

    // processes the edit widget 
    public function edit_widget($id, Request $request){
        $request->validate([
            'widget_type'  => ['required'],
            'map_filename' => ['required'],
            'widget'       => ['required'],
        ]);
        $metadata = json_decode($widget->metadata);
        // hexcode in field called 'Color'
        if($request->importColors)
            $metadata->importColors = true;

        $widget->metadata = json_encode($metadata);
        $widget = $request->widget;
        $widget->save(); // should be all I need to save the contents of the widget, right?

        return redirect()->route('profile.dashboard', ['id' => $id]);
    }

    public function delete_widget(Request $request) {
        DashboardWidget::where('user_id', Auth::id())
            ->where('id', $request->id)
            ->delete();
        return redirect()->route('profile.dashboard', ['id' => $request->dash_id]);
    }

    public function get_geojson($filename) {
        ini_set('memory_limit', -1);
        ob_start('ob_gzhandler');
        $real_filename = $filename . ".geojson";
        $geojson = FileUpload::where('filename', $real_filename)
            ->where('user_id', Auth::id())
            ->value('geojson');

        return response()->json(json_decode($geojson, true))
            ->header('Cache-Control', 'max-age=3600, public');
    }

    public function delete_dashboard(Request $request) {
        Dashboard::where('user_id', Auth::id())->where('id', $request->id)->delete();
        DashboardWidget::where('user_id', Auth::id())->where('dashboard_id', $request->id)->delete();
        return redirect()->route('home');
    }

    public function updateBounds(Request $request) {
        $request->validate([
            'widget_id' => ['required', 'integer'],
            'map_id' => ['required'],
            'bounds' => ['required', 'array'],
            'bounds._northEast.lat' => ['required', 'numeric'],
            'bounds._northEast.lng' => ['required', 'numeric'],
            'bounds._southWest.lat' => ['required', 'numeric'],
            'bounds._southWest.lng' => ['required', 'numeric'],
        ]);

        $userId = Auth::id();
        $widget = DashboardWidget::where('user_id', $userId)
            ->where('id', $request->integer('widget_id'))
            ->firstOrFail();

        // Map widgets don't have x/y axes; only charts (2..4) are refreshable here
        if (!($widget->widget_type_id > 1 && $widget->widget_type_id <= 4)) {
            return response()->json(['labels' => [], 'datasets' => []]);
        }

        $meta = json_decode($widget->metadata, true) ?: [];
        $xAxis = $meta['x_axis'] ?? null;
        $yAxis = $meta['y_axis'] ?? null;
        $mapFilename = $meta['map_filename'] ?? null;

        // don't want to link to this
        if(($meta['mapLinkID'] != $request->map_id))
            return response()->json(['labels' => 'dont']);

        // if we don't have an axis
        if(!array_key_exists('norm', $meta))
            $meta['norm'] = "NOPE";
        if ($meta['norm'] == "NOPE" && (!$xAxis || !$yAxis || !$mapFilename)) {
            return response()->json(['labels' => [], 'datasets' => ['backgroundColor' => $this->getColorArray($widget, $labels)]]);
        }

        $geo = FileUpload::where('filename', $mapFilename)
            ->where('user_id', $userId)
            ->first();

        if (!$geo) return response()->json(['labels' => [], 'datasets' => []]);

        $json = json_decode($geo->geojson, true);
        if (!is_array($json) || ($json['type'] ?? '') !== 'FeatureCollection') {
            return response()->json(['labels' => [], 'datasets' => []]);
        }

        $ne = $request->input('bounds._northEast');
        $sw = $request->input('bounds._southWest');
        $north = (float) $ne['lat']; $east  = (float) $ne['lng'];
        $south = (float) $sw['lat']; $west  = (float) $sw['lng'];

        $filtered = [];
        foreach ($json['features'] as $f) {
            $pt = $this->featureRepresentativePoint($f);
            if (!$pt) continue;
            if ($this->pointInBounds($pt['lat'], $pt['lng'], $south, $west, $north, $east)) $filtered[] = $f;
        }

        // replacing the calculation bits with a call to a function to centralize it all
        if($meta['norm'] == "NOPE")
            $results = $this->calculateChartData($xAxis, $yAxis, $filtered, $widget['widget_type_id']);
        else
            $results = $this->compressDatasets($meta['norm_axis'], $meta['norm_calc'], $filtered, 0);
        $labels = $results['labels'];
        $values = $results['values'];
        $categoryWarning = $results['catWarning'];
        unset($results);// I dunno, feels nice

        return response()->json([
            'labels' => $labels,
            'datasets' => [[
                'label' => $labels,
                'data'  => $values,
                'fill'  => true,
                'pointRadius' => 0,
                'borderWidth' => 1,
                'backgroundColor' => $this->getColorArray($widget, $labels),
            ]],
            'category_warning' => $categoryWarning,
        ]);

    }

    private function getColorArray($widgetFile, $labels){
        $metadata = json_decode($widgetFile->metadata, true);

        // initialize color map if there isn't one
        if(!array_key_exists('colorMap', $metadata)){
            $metadata['colorMap'] = array();
            if(is_array($labels)){
                // default colors from chartjs
                $defaultColors = [
                    'rgb(54, 162, 235)', // blue
                    'rgb(255, 99, 132)', // red
                    'rgb(255, 159, 64)', // orange
                    'rgb(255, 205, 86)', // yellow
                    'rgb(75, 192, 192)', // green
                    'rgb(66, 33, 99)', // purple omage
                    'rgb(201, 203, 207)' // grey
                ];
                foreach($labels as $key)//give all the labels a 'default' color
                    $metadata['colorMap'][$key] = $defaultColors[sizeof($metadata['colorMap']) % sizeof($defaultColors)];
            
                //$metadata['colorMap'][$labels[0]] = '#31220b';//testing testing
            }
            $widgetFile->metadata = json_encode($metadata, true);
            $widgetFile->save();
        }

        if (is_array($labels)) {
            $curatedColor = [];
            foreach ($labels as $key) 
                $curatedColor[] = $metadata['colorMap'][$key] ?? '#999';
            return $curatedColor;
        }
        return $metadata['colorMap'];
    }

    // public function that returns an associative array of widget colors, key => color 
    // filtering with key array is optional
    public function getWidgetColors(Request $request){
        $request->validate([
            'widget_id' => ['required', 'integer'],
        ]);
        $userId = Auth::id();
        // 1) Find the widget and ensure it belongs to the user
        $widget = DashboardWidget::where('user_id', $userId)
            ->where('id', $request->integer('widget_id'))
            ->firstOrFail();
        return response()->json(json_encode(getColorArray($widget, $request->keys), true));
    }
    // public function to call when you want to change a color in metadata['colorMap']
    public function changeColor(Request $request){
        $request->validate([
            'widget_id' => ['required', 'integer'],
            'key' => ['required', 'string'],
            'color' => ['required', 'string'],
        ]);

        $userId = Auth::id();

        // 1) Find the widget and ensure it belongs to the user
        $widget = DashboardWidget::where('user_id', $userId)
            ->where('id', $request->widget_id)
            ->firstOrFail();

        $meta = json_decode($widget->metadata, true);
        $color = $request->string('color');
        $key = $request->string('key');

        //key exists in array and our requested color is in fact a hex code
        if(array_key_exists($key, $meta['colorMap']) && (preg_match('/^#[a-f0-9]{6}$/i', $color)))
            $meta['colorMap'][$key] = $color;

        $widget->metadata = json_encode($meta, true);
        $widget->save();
    }

/**
 * Representative point for a feature.
 * - Point → that point.
 * - Polygon/MultiPolygon → first ring’s first coord (simple/fast).
 *   (For production accuracy, compute real centroid/intersection or use PostGIS.)
 */
private function featureRepresentativePoint(array $feature): ?array
{
    $geom = $feature['geometry'] ?? null;
    if (!$geom || !isset($geom['type'])) return null;

        if ($geom['type'] === 'Point') {
            $c = $geom['coordinates'] ?? null;
            if (is_array($c) && count($c) >= 2) return ['lat' => (float)$c[1], 'lng' => (float)$c[0]];
        }
        if ($geom['type'] === 'Polygon' && !empty($geom['coordinates'][0][0])) {
            $c = $geom['coordinates'][0][0];
            return ['lat' => (float)$c[1], 'lng' => (float)$c[0]];
        }
        if ($geom['type'] === 'MultiPolygon' && !empty($geom['coordinates'][0][0][0])) {
            $c = $geom['coordinates'][0][0][0];
            return ['lat' => (float)$c[1], 'lng' => (float)$c[0]];
        }
        return null;
    }

    private function pointInBounds(float $lat, float $lng, float $south, float $west, float $north, float $east): bool
    {
        return $lat >= $south && $lat <= $north && $lng >= $west && $lng <= $east;
    }
}

