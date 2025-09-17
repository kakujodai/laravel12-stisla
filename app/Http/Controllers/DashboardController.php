<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DashboardWidgetType;
use App\Models\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; //for Str::random()

class DashboardController extends Controller
{
    public function show_dashboard($id)
    {
	ob_start('ob_gzhandler');
	$userId = Auth::id();
	# Get all files
        $path = "users/{$userId}";
        $my_files = FileUpload::select('filename')->where('user_id', '=', Auth::id())->get();
        $geojson_array = [];
        $geojson_chart_array = [];
	foreach ($my_files as $my_file) {
	    $file = $my_file['filename'];
	        $geojson_array[] = ['filename' => preg_replace('/[^A-Za-z0-9\_\.]/', '', basename($file))]; //$geojson_array[] = geojson_array.append()
	    }
	    # Get Generalized Dashboard
        $dashboard_info = Dashboard::where('user_id', '=', $userId)
            ->where('id', '=', $id)
            ->get(); // Get the widget with the right id, but ensure we don't open widgets that aren't ours by filtering by user id.
        $get_widgets = DashboardWidget::where('dashboard_id', '=', $id)
	        ->get(); // Get widgets for this dashboard

	    # Iterate Widgets
        foreach ($get_widgets as $get_widget) {
            $decode_metadata = json_decode($get_widget['metadata'], true);
            if ($get_widget['widget_type_id'] == 1) {
                $get_map_filename= $decode_metadata['map_filename'];
                $get_widget['map_json'] = Storage::get("users/{$userId}/{$get_map_filename}");
		        $get_widget['random_id'] = Str::random();
		        $get_widget['filename'] = preg_replace('/[^A-Za-z0-9\_\.]/', '', basename($get_map_filename));
            }
	}
	    $get_widget_types = DashboardWidgetType::get(); // Get all widget types
        $array = ['dashboard_info' => $dashboard_info[0], 'widgets' => $get_widgets, 'widget_types' => $get_widget_types, 'all_geojsons' => $geojson_array];

        return view('profile.dashboard', $array);
    }

    public function add_widgets($id) { // Show the add widgets page
        $dashboard_info = Dashboard::where('user_id', '=', Auth::id())
            ->where('id', '=', $id)
            ->get();
        $my_files = FileUpload::select('filename')->where('user_id', '=', Auth::id())->get(); # Limit to filename only because it will be sloooooow with json in the db
        $get_widget_types = DashboardWidgetType::get(); // Get all widget types
        $array = ['dashboard_info' => $dashboard_info[0], 'widget_types' => $get_widget_types, 'files' => $my_files];
        return view('profile.add-widgets', $array);
    }

    public function add_widget($id, Request $request) { // Add the widget to the database
        $request->validate([
            'widget_type' => ['required'],
        ]);
        $widget = new DashboardWidget;
        $widget->user_id = auth()->id();
        $widget->dashboard_id = $id;
        $widget->name = $request->widget_name;
        $widget->widget_type_id = $request->widget_type;
        if ($request->widget_type == 1) { // Handle Maps
            $metadata = json_encode(['map_filename' => $request->map_filename]);
        };
        $widget->metadata = $metadata;
        $widget->save();
        return redirect()->route('profile.dashboard', ['id' => $id]);
    }

    public function delete_widget(Request $request) {
        $del_id = DashboardWidget::where('user_id', '=', Auth::id())->where('id', '=', $request->id);
        $del_id->delete();
        return redirect()->route('profile.dashboard', ['id' => $request->dash_id]);
    }

    public function get_geojson($filename) {
	    ob_start('ob_gzhandler');
	    $real_filename = $filename.".geojson";
	    $getfiles = FileUpload::select('geojson')
		    	->where('filename', '=', $real_filename)
			->where('user_id', '=', Auth::id())
			->get();
	    return response()->json(json_decode($getfiles->value('geojson'), true));
    }
}
