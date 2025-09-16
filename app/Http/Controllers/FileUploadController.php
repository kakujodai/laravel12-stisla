<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
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
	$filename = $file->getClientOriginalName();
	$file_extension = pathinfo($filename, PATHINFO_EXTENSION);
	$geojson_chart_metadata = [];
        if (!FileUpload::where('filename', '=', $filename)->where('user_id', '=', $userId)->exists()) {
 	    if (Storage::putFileAs($path, $file, $filename)) {
		if ($file_extension == 'geojson') { # Handle getting the properties for the upload metadata
                    $read_file = Storage::get("$path/$filename"); //laravel's storage facade Storage::get($file)
                    $json_version = json_decode($read_file, true);
 		    foreach ($json_version['features'][0]['properties'] as $key => $value) { # Get the first feature because they all have the same properties
                        $geojson_chart_metadata['x_axis'][] = $key; # Add all to the x axis
                        if (is_numeric($value)) {
                            $geojson_chart_metadata['y_axis'][] = $key; # Only add numerical values to y axis
                        }
                    }
		}

                $filePath = storage_path("app/$path/$filename");
                # If we placed the file successfully
                $file_upload = new FileUpload;
                $file_upload->user_id = $userId;
		$file_upload->filename = $filename;
		$file_upload->properties_metadata = json_encode($geojson_chart_metadata);
                $file_upload->md5 = md5_file($filePath);
                $file_upload->title = $title;
                $file_upload->save();
                return back()->with('success', 'File uploaded successfully!');
            } else {
                return back()->with('error', 'We were unable to save the file to the system. Please try again');
            }
        } else {
            return back()->with('error', 'This file already exists within the system');
        }
    }
}
