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

                    $values_md = [];
                    $categoryWarning = false;
                    $maxCategories = 100;

                    if (($decode_metadata['y_axis'] ?? null) === 'COUNT') {
                        foreach (($json_version['features'] ?? []) as $feature) {
                            $xv = $feature['properties'][$decode_metadata['x_axis']] ?? null;
                            if ($xv === null) continue;
                            $values_md[$xv] = ($values_md[$xv] ?? 0) + 1;
                        }
                    } else {
                        foreach (($json_version['features'] ?? []) as $feature) {
                            // make property names case-insensitive
                            $props = array_change_key_case($feature['properties'], CASE_LOWER);
                            $xv = $props[strtolower($decode_metadata['x_axis'])] ?? null;
                            $yv = $props[strtolower($decode_metadata['y_axis'])] ?? null;

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
                    if ($get_widget['widget_type_id'] == 4) {
                        $labels = array_map(static fn($v) => (string)$v, $labels);
                    }
                    $values = array_values($values_md);

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
        } elseif ((int)$request->widget_type !== 1) {
            $request->validate([
                'x_axis'    => ['required'],
                'y_axis'    => ['required'],
                'mapLinkID' => ['nullable', 'string'],
            ]);
        }

        if (!$request->widget_name) {
            $title = FileUpload::where('user_id', Auth::id())
                ->where('filename', $request->map_filename)
                ->value('title');

            if ((int)$request->widget_type > 1 && (int)$request->widget_type < 5) {
                $widget_name = ($request->y_axis === 'COUNT')
                    ? "{$request->y_axis} BY {$request->x_axis}"
                    : "SUM OF {$request->y_axis} BY {$request->x_axis}";
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
        } else {
            $metadata = [
                'x_axis'       => $request->x_axis,
                'y_axis'       => $request->y_axis,
                'map_filename' => $request->map_filename,
                'mapLinkID'    => $request->mapLinkID ?: 'noLink321π',
            ];
        }

        $widget->metadata = json_encode($metadata);
        $widget->save();

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
            'map_id' => ['required', 'integer'],
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

        $labels = array(); //trust me here
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
        if (!$xAxis || !$yAxis || !$mapFilename) {
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

        $values_md = [];
        $categoryWarning = false;
        $maxCategories = 100;

        if (strtoupper($yAxis) === 'COUNT') {
            foreach ($filtered as $f) {
                $key = $f['properties'][$xAxis] ?? null;
                if ($key === null) continue;
                $values_md[$key] = ($values_md[$key] ?? 0) + 1;
            }
        } else {
            foreach ($filtered as $f) {
                $key = $f['properties'][$xAxis] ?? null;
                $val = $f['properties'][$yAxis] ?? null;
                if ($key === null || !is_numeric($val)) continue;
                $values_md[$key] = ($values_md[$key] ?? 0) + (float)$val;
            }
        }

        // Cap category count
        if (count($values_md) > $maxCategories) {
            $values_md = array_slice($values_md, 0, $maxCategories, true);
            $categoryWarning = true;
        }

        $labels = array_keys($values_md);
        $values = array_values($values_md);

        return response()->json([
            'labels' => $labels,
            'datasets' => [[
                'label' => $xAxis,
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
                $defaultColors = ['#FF692A', '#05DF72', '#8E51FF', '#E12AFB', '#FFD230'];// default colors
                foreach($labels as $key)//give all the labels a 'default' color
                    if(!array_key_exists($key, $metadata['colorMap']))
                        $metadata['colorMap'][$key] = $defaultColors[sizeof($metadata['colorMap']) % sizeof($defaultColors)];
            
                //$metadata['colorMap'][$labels[0]] = '#31220b';//testing testing
                }

            $widgetFile->metadata = json_encode($metadata, true);
            $widgetFile->save();
        }

        if (is_array($labels)) {
            $curatedColor = [];
            foreach ($labels as $key) $curatedColor[] = $metadata['colorMap'][$key] ?? '#999';
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

