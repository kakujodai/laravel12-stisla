<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use App\Models\FileUpload;


class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'my_file' => [
                'required',
                #File::types(['gpkg', 'shp', 'geojson'])
                #    ->max(50 * 1024), // Max 50MB
            ],
        ]);
        $file = $request->file('my_file');
        $title = $request->title;
        $userId = auth()->id(); // Assuming authenticated user
        $path = "users/{$userId}";
        $filename = preg_replace('/[^A-Za-z0-9\_\.]/', '', basename($file->getClientOriginalName())); # Make sure we remove wonky characters from the filename
        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filename_only = pathinfo($filename, PATHINFO_FILENAME);
        $geojson_chart_metadata = [];
        if (!FileUpload::where('filename', '=', $filename)->where('user_id', '=', $userId)->exists()) {
            if (Storage::putFileAs($path, $file, $filename)) {
                $filePath = storage_path("app/$path/$filename");
                if ($file_extension == 'geojson') { # Handle getting the properties for the upload metadata
                    $read_file = Storage::get("$path/$filename"); //laravel's storage facade Storage::get($file)
                    $json_version = json_decode($read_file, true);
                    foreach ($json_version['features'][0]['properties'] as $key => $value) { # Get the first feature because they all have the same properties
                        $geojson_chart_metadata['x_axis'][] = $key; # Add all to the x axis
                        if (is_numeric($value)) {
                            $geojson_chart_metadata['y_axis'][] = $key; # Only add numerical values to y axis
                        }
                    }
                    # If we placed the file successfully
                    $file_upload = new FileUpload;
                    $file_upload->user_id = $userId;
		    $file_upload->filename = $filename;
		    $file_upload->geojson = $read_file;
                    $file_upload->properties_metadata = json_encode($geojson_chart_metadata);
                    $file_upload->md5 = md5_file($filePath);
                    $file_upload->title = $title;
                    $file_upload->save();
                } elseif ($file_extension == 'gpkg' || $file_extension == 'geopkg') {
                    exec("/bin/ogrinfo $filePath", $output_array);
                    $layer = 1;
                    foreach ($output_array as $line) {
                        $firstChar = substr($line, 0, 1);
                        if (is_numeric($firstChar)) { # Check for a layer line
                            $parts = explode(':', $line);
                            $area_text_with_parentheses = $parts[1]; // This will be ex:" American Indian-Alaska Native-Native Hawaiian Area (Multi Polygon)"
                            // Trim whitespace from the beginning of the string
                            $area_text_with_parentheses = trim($area_text_with_parentheses);
                            // Remove the parentheses and their contents from the end
                            $final_output = preg_replace('/\s*\(.*\)$/', '', $area_text_with_parentheses);
                            $geojson_filename = str_replace('.'.$file_extension, '', $filePath)."_layer$layer.geojson";
                            $geojson_filename_basename = basename($geojson_filename);
                            #echo "/bin/ogr2ogr -f GeoJSON -t_srs EPSG:4326 ".$geojson_filename." $filePath '$final_output'";
                            exec("/bin/ogr2ogr -f GeoJSON -t_srs EPSG:4326 ".$geojson_filename." $filePath '$final_output'", $convert_output);
                            $read_file = Storage::get("$path/$geojson_filename_basename"); //laravel's storage facade Storage::get($file)
                            $json_version = json_decode($read_file, true);
                            foreach ($json_version['features'][0]['properties'] as $key => $value) { # Get the first feature because they all have the same properties
                                $geojson_chart_metadata['x_axis'][] = $key; # Add all to the x axis
                                if (is_numeric($value)) {
                                    $geojson_chart_metadata['y_axis'][] = $key; # Only add numerical values to y axis
                                }
                            }
                            $file_upload = new FileUpload;
                            $file_upload->user_id = $userId;
                            $file_upload->filename = $geojson_filename_basename;
		    	    $file_upload->geojson = $read_file;
                            $file_upload->properties_metadata = json_encode($geojson_chart_metadata);
                            $file_upload->md5 = md5_file($filePath);
                            $file_upload->title = "$title Layer $layer";
                            $file_upload->save();
			    $layer = $layer + 1;
                        }
		    }
		    Storage::delete([$filePath]); # Delete the original dpkg file
                }
                return back()->with('success', 'File uploaded successfully!');
            } else {
                return back()->with('error', 'We were unable to save the file to the system. Please try again');
            }
        } else {
            return back()->with('error', 'This file already exists within the system');
        }
    }
}
