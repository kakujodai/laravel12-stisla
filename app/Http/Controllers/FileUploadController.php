<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use App\Models\FileUpload;


class FileUploadController extends Controller
{
    // Helper to strip BOM and ensure UTF-8 text
// private function stripBomToUtf8(string $s): string
// {
//     // UTF-8 BOM
//     if (strncmp($s, "\xEF\xBB\xBF", 3) === 0) {
//         return substr($s, 3);
//     }
//     // UTF-32 LE BOM
//     if (strncmp($s, "\xFF\xFE\x00\x00", 4) === 0) {
//         return mb_convert_encoding(substr($s, 4), 'UTF-8', 'UTF-32LE');
//     }
//     // UTF-32 BE BOM
//     if (strncmp($s, "\x00\x00\xFE\xFF", 4) === 0) {
//         return mb_convert_encoding(substr($s, 4), 'UTF-8', 'UTF-32BE');
//     }
//     // UTF-16 LE BOM
//     if (strncmp($s, "\xFF\xFE", 2) === 0) {
//         return mb_convert_encoding(substr($s, 2), 'UTF-8', 'UTF-16LE');
//     }
//     // UTF-16 BE BOM
//     if (strncmp($s, "\xFE\xFF", 2) === 0) {
//         return mb_convert_encoding(substr($s, 2), 'UTF-8', 'UTF-16BE');
//     }
//     // No BOM — try to ensure it's UTF-8 (optional safety net)
//     return mb_convert_encoding($s, 'UTF-8', 'UTF-8');
// }

private function safeDecodeJson(string $raw): array
    {
        // 1) Strip/normalize
        // $clean = $this->stripBomToUtf8($raw);

        // 2) First attempt
        $decoded = json_decode($clean, true);
        if ($decoded !== null || json_last_error() === JSON_ERROR_NONE) {
            return [$clean, $decoded];
        }

        // 3) Fallback: try broader transcode (handles some odd encodings)
        $fallback = mb_convert_encoding($clean, 'UTF-8', 'UTF-8, UTF-16LE, UTF-16BE, UTF-32LE, UTF-32BE, ISO-8859-1, Windows-1252');
        $decoded2 = json_decode($fallback, true);

        return [$fallback, $decoded2];
    }

    public function upload(Request $request) {
        
        ini_set('max_execution_time', 0);
        $request->validate([
	        'title' => 'required|max:255',
            'my_file' => 'required'
        ]);
        $gdal_path = config('gdal_path');
        $upload_limit = 15728640;
        $file = $request->file('my_file');
        $title = $request->title;
        $userId = auth()->id(); // Assuming authenticated user
        $path = "users/{$userId}";
        $filename = preg_replace('/[^A-Za-z0-9\_\.]/', '', basename($file->getClientOriginalName())); # Make sure we remove wonky characters from the filename
        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filename_only = str_replace('.', '_', pathinfo($filename, PATHINFO_FILENAME));
        $filename = "$filename_only.$file_extension";
        $geojson_chart_metadata = [];
        if (!FileUpload::where('filename', '=', $filename)->where('user_id', '=', $userId)->exists()) {
            if (Storage::putFileAs($path, $file, $filename)) {
                $filePath = storage_path("app/$path/$filename");
                if ($file_extension == 'geojson') { # Handle getting the properties for the upload metadata
                    $fileSize = filesize($filePath);
                    $read_file = Storage::get("$path/$filename"); # Laravel's storage facade Storage::get($file)
		            $json_version = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $read_file), true);
                    foreach ($json_version['features'][0]['properties'] as $key => $value) { # Get the first feature because they all have the same properties
                        $geojson_chart_metadata['x_axis'][] = $key; # Add all to the x axis
                        if (is_numeric($value)) {
                            $geojson_chart_metadata['y_axis'][] = $key; # Only add numerical values to y axis
                        }
                    }
                    if ($fileSize > $upload_limit) {
                        $read_file = null;
                    } else {
                        $read_file = json_encode($json_version);
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
                    exec($gdal_path."ogrinfo $filePath", $output_array); # Get All of the layers of the geopkg
                    $layer = 1; 
                    foreach ($output_array as $line) { # Iterate lines until we find one that starts with a number \/
                        $firstChar = substr($line, 0, 1); # Get first char
                        if (is_numeric($firstChar)) { # Check for a layer line
                            $parts = explode(':', $line);
                            $area_text_with_parentheses = $parts[1]; # This will be ex:" American Indian-Alaska Native-Native Hawaiian Area (Multi Polygon)"
                            # Trim whitespace from the beginning of the string
                            $area_text_with_parentheses = trim($area_text_with_parentheses);
                            # Remove the parentheses and their contents from the end
                            $final_output = preg_replace('/\s*\(.*\)$/', '', $area_text_with_parentheses);
                            # Renaming erthing to geojson with the layer#
                            $geojson_filename = str_replace('.'.$file_extension, '', $filePath)."_layer$layer.geojson";
                            # Get his new basename with the layer
                            $geojson_filename_basename = basename($geojson_filename);
                            # Run the conversion prcess for each layer now that we know what layer name to provide the program. Make sure we set it to geojson ver EPSG:4326 or it'll be long/lat vs lat/long
                            exec($gdal_path."ogr2ogr -f GeoJSON -t_srs EPSG:4326 ".$geojson_filename." $filePath '$final_output'", $convert_output);
                            # Like above for a native geojson, we need to open it to get the metadata needed later for charts
                            $read_file = Storage::get("$path/$geojson_filename"); # Laravel's storage facade Storage::get($file)
                            $json_version = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $read_file), true); # Put it in a pretty array
                            foreach ($json_version['features'][0]['properties'] as $key => $value) { # Get the first feature because they all have the same properties
                                $geojson_chart_metadata['x_axis'][] = $key; # Add all to the x axis
                                if (is_numeric($value)) {
                                    $geojson_chart_metadata['y_axis'][] = $key; # Only add numerical values to y axis
                                }
                            }
                            $fileSize = strlen($read_file);
                            if ($fileSize > $upload_limit) {
                                $read_file = null;
                            } else {
                                $read_file = json_encode($json_version);
                            }
                            # Add it to the db
                            $file_upload = new FileUpload;
                            $file_upload->user_id = $userId;
                            $file_upload->filename = $geojson_filename_basename;
		    	            $file_upload->geojson = $read_file;
                            $file_upload->properties_metadata = json_encode($geojson_chart_metadata);
                            $file_upload->md5 = md5_file($filePath);
                            $file_upload->title = "$final_output"; # Lets use the layer name extracted as the title
                            $file_upload->save();
			                $layer = $layer + 1;
                        }   
		            }
		            Storage::delete([$filePath]); # Delete the original dpkg file because it's smelly
                } elseif ($file_extension == 'pbf') {
                    exec($gdal_path."ogrinfo $filePath", $output_array); # Get All of the layers of the geopkg
                    $layer = 1; 
                    foreach ($output_array as $line) { # Iterate lines until we find one that starts with a number \/
                        $firstChar = substr($line, 0, 1); # Get first char
                        if (is_numeric($firstChar)) { # Check for a layer line
                            $parts = explode(':', $line);
                            $area_text_with_parentheses = $parts[1]; # This will be ex:" American Indian-Alaska Native-Native Hawaiian Area (Multi Polygon)"
                            # Trim whitespace from the beginning of the string
                            $area_text_with_parentheses = trim($area_text_with_parentheses);
                            # Remove the parentheses and their contents from the end
                            $final_output = preg_replace('/\s*\(.*\)$/', '', $area_text_with_parentheses);
                            # Renaming erthing to geojson with the layer#
                            $geojson_filename = str_replace('.'.$file_extension, '', $filePath)."_layer$layer.geojson";
                            # Get his new basename with the layer
                            $geojson_filename_basename = basename($geojson_filename);
                            # Run the conversion prcess for each layer now that we know what layer name to provide the program. Make sure we set it to geojson ver EPSG:4326 or it'll be long/lat vs lat/long
                            exec($gdal_path."ogr2ogr -f GeoJSON -t_srs EPSG:4326 ".$geojson_filename." $filePath '$final_output'", $convert_output);
                            # Like above for a native geojson, we need to open it to get the metadata needed later for charts
                            $read_file = Storage::get("$path/$geojson_filename_basename"); # Laravel's storage facade Storage::get($file)
                            $json_version = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $read_file), true); # Put it in a pretty array
                            foreach ($json_version['features'][0]['properties'] as $key => $value) { # Get the first feature because they all have the same properties
                                $geojson_chart_metadata['x_axis'][] = $key; # Add all to the x axis
                                if (is_numeric($value)) {
                                    $geojson_chart_metadata['y_axis'][] = $key; # Only add numerical values to y axis
                                }
                            }
                            $fileSize = strlen($read_file);
                            if ($fileSize > $upload_limit) {
                                $read_file = null;
                            } else {
                                $read_file = json_encode($json_version);
                            }
                            # Add it to the db
                            $file_upload = new FileUpload;
                            $file_upload->user_id = $userId;
                            $file_upload->filename = $geojson_filename_basename;
		    	            $file_upload->geojson = $read_file;
                            $file_upload->properties_metadata = json_encode($geojson_chart_metadata);
                            $file_upload->md5 = md5_file($filePath);
                            $file_upload->title = "$final_output"; # Lets use the layer name extracted as the title
                            $file_upload->save();
			                $layer = $layer + 1;
                        }   
		            }
		            Storage::delete([$filePath]); # Delete the original dpkg file because it's smelly
               } elseif ($file_extension == 'shp') { # TODO: We need to change upload to allow more than one file to support this because it needs a shx and shp file to work
                    exec($gdal_path."ogrinfo $filePath", $output_array); # Get All of the layers of the shape file
                    $layer = 1; 
                    foreach ($output_array as $line) { # Iterate lines until we find one that starts with a number \/
                        $firstChar = substr($line, 0, 1); # Get first char
                        if (is_numeric($firstChar)) { # Check for a layer line
                            $parts = explode(':', $line);
                            $area_text_with_parentheses = $parts[1]; # This will be ex:" American Indian-Alaska Native-Native Hawaiian Area (Multi Polygon)"
                            # Trim whitespace from the beginning of the string
                            $area_text_with_parentheses = trim($area_text_with_parentheses);
                            # Remove the parentheses and their contents from the end
                            $final_output = preg_replace('/\s*\(.*\)$/', '', $area_text_with_parentheses);
                            # Renaming erthing to geojson with the layer#
                            $geojson_filename = str_replace('.'.$file_extension, '', $filePath)."_layer$layer.geojson";
                            # Get his new basename with the layer
                            $geojson_filename_basename = basename($geojson_filename);
                            # Run the conversion prcess for each layer now that we know what layer name to provide the program. Make sure we set it to geojson ver EPSG:4326 or it'll be long/lat vs lat/long
                            exec($gdal_path."ogr2ogr -f GeoJSON -t_srs EPSG:4326 ".$geojson_filename." $filePath '$final_output'", $convert_output);
                            # Like above for a native geojson, we need to open it to get the metadata needed later for charts
                            $read_file = Storage::get("$path/$geojson_filename_basename"); # Laravel's storage facade Storage::get($file)
                            $json_version = json_encode($read_file, true);
                            foreach ($json_version['features'][0]['properties'] as $key => $value) { # Get the first feature because they all have the same properties
                                $geojson_chart_metadata['x_axis'][] = $key; # Add all to the x axis
                                if (is_numeric($value)) {
                                    $geojson_chart_metadata['y_axis'][] = $key; # Only add numerical values to y axis
                                }
                            }
                            $fileSize = strlen($read_file);
                            if ($fileSize > $upload_limit) {
                                $read_file = null;
                            } else {
                                $read_file = json_encode($json_version);
                            }
                            # Add it to the db
                            $file_upload = new FileUpload;
                            $file_upload->user_id = $userId;
                            $file_upload->filename = $geojson_filename_basename;
		    	            $file_upload->geojson = $read_file;
                            $file_upload->properties_metadata = json_encode($geojson_chart_metadata);
                            $file_upload->md5 = md5_file($filePath);
                            $file_upload->title = "$final_output (L$layers)"; # Lets use the layer name extracted as the title
                            $file_upload->save();
			                $layer = $layer + 1;
                        }   
		            }
		            Storage::delete([$filePath]); # Delete the original dpkg file because it's smelly
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
