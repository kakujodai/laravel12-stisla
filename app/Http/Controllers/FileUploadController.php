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
private function stripBomToUtf8(string $s): string
{
    // UTF-8 BOM
    if (strncmp($s, "\xEF\xBB\xBF", 3) === 0) {
        return substr($s, 3);
    }
    // UTF-32 LE BOM
    if (strncmp($s, "\xFF\xFE\x00\x00", 4) === 0) {
        return mb_convert_encoding(substr($s, 4), 'UTF-8', 'UTF-32LE');
    }
    // UTF-32 BE BOM
    if (strncmp($s, "\x00\x00\xFE\xFF", 4) === 0) {
        return mb_convert_encoding(substr($s, 4), 'UTF-8', 'UTF-32BE');
    }
    // UTF-16 LE BOM
    if (strncmp($s, "\xFF\xFE", 2) === 0) {
        return mb_convert_encoding(substr($s, 2), 'UTF-8', 'UTF-16LE');
    }
    // UTF-16 BE BOM
    if (strncmp($s, "\xFE\xFF", 2) === 0) {
        return mb_convert_encoding(substr($s, 2), 'UTF-8', 'UTF-16BE');
    }
    // No BOM — try to ensure it's UTF-8 (optional safety net)
    return mb_convert_encoding($s, 'UTF-8', 'UTF-8');
}

private function safeDecodeJson(string $raw): array
    {
        // 1) Strip/normalize
        $clean = $this->stripBomToUtf8($raw);

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

    public function upload(Request $request)
    {
        $request->validate([
            'my_file' => ['required'],
            'title'   => ['required'],
        ]);

        $file    = $request->file('my_file');
        $title   = $request->title;
        $userId  = auth()->id(); // Assuming authenticated user
        $path    = "users/{$userId}";

        // Sanitize filename
        $filename        = preg_replace('/[^A-Za-z0-9_.]/', '', basename($file->getClientOriginalName()));
        $file_extension  = pathinfo($filename, PATHINFO_EXTENSION);
        $filename_only   = pathinfo($filename, PATHINFO_FILENAME);
        $geojson_metadata = [];

        if (!FileUpload::where('filename', '=', $filename)->where('user_id', '=', $userId)->exists()) {
            if (Storage::putFileAs($path, $file, $filename)) {
                $filePath = storage_path("app/$path/$filename");

                if ($file_extension === 'geojson') {
                    // Read & normalize
                    $raw = Storage::get("$path/$filename");
                    [$clean, $json] = $this->safeDecodeJson($raw);

                    if (!is_array($json)) {
                        return back()->with('error', 'Invalid or unreadable GeoJSON (encoding/JSON error).');
                    }

                    // Build simple chart metadata using first feature’s properties (if present)
                    if (!empty($json['features'][0]['properties']) && is_array($json['features'][0]['properties'])) {
                        foreach ($json['features'][0]['properties'] as $key => $value) {
                            $geojson_metadata['x_axis'][] = $key;
                            if (is_numeric($value)) {
                                $geojson_metadata['y_axis'][] = $key;
                            }
                        }
                    }

                    // Save
                    $file_upload                     = new FileUpload;
                    $file_upload->user_id            = $userId;
                    $file_upload->filename           = $filename;
                    $file_upload->geojson            = $clean; // store normalized text
                    $file_upload->properties_metadata= json_encode($geojson_metadata);
                    $file_upload->md5                = md5_file($filePath);
                    $file_upload->title              = $title;
                    $file_upload->save();

                } elseif ($file_extension === 'gpkg' || $file_extension === 'geopkg') {
                    // List layers
                    exec("/bin/ogrinfo $filePath", $output_array);
                    $layer = 1;

                    foreach ($output_array as $line) {
                        $firstChar = substr($line, 0, 1);
                        if (is_numeric($firstChar)) {
                            $parts = explode(':', $line);
                            $area_text_with_parentheses = isset($parts[1]) ? trim($parts[1]) : '';
                            $final_output = preg_replace('/\s*\(.*\)$/', '', $area_text_with_parentheses);

                            // Convert layer to GeoJSON EPSG:4326
                            $geojson_filename = str_replace('.' . $file_extension, '', $filePath) . "_layer$layer.geojson";
                            $geojson_filename_basename = basename($geojson_filename);

                            exec("/bin/ogr2ogr -f GeoJSON -t_srs EPSG:4326 " . $geojson_filename . " $filePath '$final_output'", $convert_output);

                            // Read & normalize converted GeoJSON
                            $raw_layer = Storage::get("$path/$geojson_filename_basename");
                            [$clean_layer, $json_layer] = $this->safeDecodeJson($raw_layer);

                            $geojson_metadata = []; // reset per layer
                            if (is_array($json_layer) && !empty($json_layer['features'][0]['properties']) && is_array($json_layer['features'][0]['properties'])) {
                                foreach ($json_layer['features'][0]['properties'] as $key => $value) {
                                    $geojson_metadata['x_axis'][] = $key;
                                    if (is_numeric($value)) {
                                        $geojson_metadata['y_axis'][] = $key;
                                    }
                                }
                            }

                            $file_upload                      = new FileUpload;
                            $file_upload->user_id             = $userId;
                            $file_upload->filename            = $geojson_filename_basename;
                            $file_upload->geojson             = $clean_layer;
                            $file_upload->properties_metadata = json_encode($geojson_metadata);
                            $file_upload->md5                 = md5_file($filePath);
                            $file_upload->title               = "$final_output";
                            $file_upload->save();

                            $layer++;
                        }
                    }

                    // Delete original gpkg (absolute path)
                    @unlink($filePath);

                } elseif ($file_extension === 'shp') {
                    // NOTE: Shapefiles need sidecar files; this branch assumes they exist server-side.
                    exec("/bin/ogrinfo $filePath", $output_array);
                    $layer = 1;

                    foreach ($output_array as $line) {
                        $firstChar = substr($line, 0, 1);
                        if (is_numeric($firstChar)) {
                            $parts = explode(':', $line);
                            $area_text_with_parentheses = isset($parts[1]) ? trim($parts[1]) : '';
                            $final_output = preg_replace('/\s*\(.*\)$/', '', $area_text_with_parentheses);

                            $geojson_filename = str_replace('.' . $file_extension, '', $filePath) . "_layer$layer.geojson";
                            $geojson_filename_basename = basename($geojson_filename);

                            exec("/bin/ogr2ogr -f GeoJSON -t_srs EPSG:4326 " . $geojson_filename . " $filePath '$final_output'", $convert_output);

                            $raw_layer = Storage::get("$path/$geojson_filename_basename");
                            [$clean_layer, $json_layer] = $this->safeDecodeJson($raw_layer);

                            $geojson_metadata = []; // reset per layer
                            if (is_array($json_layer) && !empty($json_layer['features'][0]['properties']) && is_array($json_layer['features'][0]['properties'])) {
                                foreach ($json_layer['features'][0]['properties'] as $key => $value) {
                                    $geojson_metadata['x_axis'][] = $key;
                                    if (is_numeric($value)) {
                                        $geojson_metadata['y_axis'][] = $key;
                                    }
                                }
                            }

                            $file_upload                      = new FileUpload;
                            $file_upload->user_id             = $userId;
                            $file_upload->filename            = $geojson_filename_basename;
                            $file_upload->geojson             = $clean_layer;
                            $file_upload->properties_metadata = json_encode($geojson_metadata);
                            $file_upload->md5                 = md5_file($filePath);
                            $file_upload->title               = "$final_output (L$layer)";
                            $file_upload->save();

                            $layer++;
                        }
                    }

                    // Delete original shp (absolute path)
                    @unlink($filePath);
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
