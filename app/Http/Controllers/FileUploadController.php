<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\FileUpload;

class FileUploadController extends Controller
{

private function safeDecodeJson(string $raw): array
    {
        // 1) Strip/normalize
        $clean = $raw;
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

private function buildChartMetadata(array $json_version): array
    {
        $geojson_chart_metadata = [];

        foreach ($json_version['features'][0]['properties'] as $key => $value) { # Get the first feature because they all have the same properties
            $geojson_chart_metadata['x_axis'][] = $key; # Add all to the x axis
            if (is_numeric($value)) {
                $geojson_chart_metadata['y_axis'][] = $key; # Only add numerical values to y axis
            }
        }

        return $geojson_chart_metadata;
    }

private function decodeGeojsonOrFail(string $raw, bool $converted = false): array
    {
        try {
            $json_version = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            if ($converted) {
                throw new \RuntimeException('Converted GeoJSON output is not valid JSON.');
            }
            throw new \RuntimeException('Invalid GeoJSON/JSON file.');
        }

        if (empty($json_version['features']) || !isset($json_version['features'][0]['properties'])) {
            if ($converted) {
                throw new \RuntimeException('Converted GeoJSON has no features/properties.');
            }
            throw new \RuntimeException('This GeoJSON is missing features/properties or could not be parsed.');
        }

        return $json_version;
    }

private function saveGeojsonRow(int $userId, string $filename, string $geojsonRaw, array $geojson_chart_metadata, string $md5SourcePath, string $title): void
    {
        $file_upload = new FileUpload;
        $file_upload->user_id = $userId;
        $file_upload->filename = $filename;
        $file_upload->geojson = $geojsonRaw;
        $file_upload->properties_metadata = json_encode($geojson_chart_metadata);
        $file_upload->md5 = md5_file($md5SourcePath);
        $file_upload->title = $title;
        $file_upload->save();
    }

private function convertAndSaveLayers(string $gdal_path, string $filePath, string $file_extension, int $userId, int $upload_limit): void
    {
        exec($gdal_path . "ogrinfo " . escapeshellarg($filePath), $output_array); # Get All of the layers of the geopkg

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
                $geojson_filename = str_replace('.' . $file_extension, '', $filePath) . "_layer$layer.geojson";
                # Get his new basename with the layer
                $geojson_filename_basename = basename($geojson_filename);

                # Run the conversion prcess for each layer now that we know what layer name to provide the program. Make sure we set it to geojson ver EPSG:4326 or it'll be long/lat vs lat/long
                exec(
                    $gdal_path . "ogr2ogr -f GeoJSON -t_srs EPSG:4326 "
                    . escapeshellarg($geojson_filename) . " "
                    . escapeshellarg($filePath) . " "
                    . escapeshellarg($final_output)
                );

                $read_file = file_get_contents($geojson_filename);
                if ($read_file === false) {
                    throw new \RuntimeException('Conversion succeeded but the GeoJSON output could not be read.');
                }

                $fileSize = strlen($read_file);
                if ($fileSize > $upload_limit) {
                    $mb = round($upload_limit / 1024 / 1024);
                    throw new \RuntimeException("This dataset is too large after conversion. {$mb}MB is the maximum.");
                }

                $json_version = $this->decodeGeojsonOrFail($read_file, true);
                $geojson_chart_metadata = $this->buildChartMetadata($json_version);

                # Add it to the db
                $this->saveGeojsonRow(
                    $userId,
                    $geojson_filename_basename,
                    json_encode($json_version),
                    $geojson_chart_metadata,
                    $filePath,
                    "$final_output" # Lets use the layer name extracted as the title
                );

                $layer = $layer + 1;
            }
        }
    }

    public function upload(Request $request) {
        
        ini_set('max_execution_time', 0);
        $request->validate(
            [
	        'title' => 'required|max:255',
            'my_file' => 'required|file|max:51200',
            ],
            [
            'my_file.extensions' => 'This file type is not allowed. Accepted: GeoJSON/JSON, GeoPackage, PBF, or a ZIP shapefile.',
            'my_file.max' => 'This file is too large. Maximum size is 50MB.'
            ]
        );
        $gdal_path = config('gdal_path');

        $upload_limit = 52428800; //50mb

        $file = $request->file('my_file');
        $title = $request->title;
        $userId = auth()->id(); // Assuming authenticated user
        $path = "users/{$userId}";

        if (!$file || !$file->isValid()) { //catch all
            return back()->withErrors(['my_file' => 'Upload failed. Please try again.']);
        }

        //size check, avoid post too large exception
        if ($file->getSize() > $upload_limit) {
            $mb = round($upload_limit / 1024 / 1024);
            return back()->withErrors(['my_file' => "This file is too large. {$mb}MB is the maximum."]);
        }

        //safe file names
        $filename = preg_replace('/[^A-Za-z0-9\_\.]/', '', basename($file->getClientOriginalName())); # Make sure we remove wonky characters from the filename
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filename_only = str_replace('.', '_', pathinfo($filename, PATHINFO_FILENAME));
        $filename = "$filename_only.$file_extension";

        if (!FileUpload::where('filename', '=', $filename)->where('user_id', '=', $userId)->exists()) {

            if (Storage::putFileAs($path, $file, $filename)) {

                $filePath = storage_path("app/$path/$filename");

                try {

                    if ($file_extension == 'geojson' || $file_extension == 'json') { # Handle getting the properties for the upload metadata

                        $read_file = Storage::get("$path/$filename"); # Laravel's storage facade Storage::get($file)

                        $fileSize = strlen($read_file);
                        if ($fileSize > $upload_limit) {
                            Storage::delete("$path/$filename");
                            $mb = round($upload_limit / 1024 / 1024);
                            return back()->withErrors(['my_file' => "This file is too large. {$mb}MB is the maximum."]);
                        }

                        $json_version = $this->decodeGeojsonOrFail($read_file, false);
                        $geojson_chart_metadata = $this->buildChartMetadata($json_version);

                        # If we placed the file successfully
                        $this->saveGeojsonRow(
                            $userId,
                            $filename,
                            json_encode($json_version),
                            $geojson_chart_metadata,
                            $filePath,
                            $title
                        );

                    } elseif ($file_extension == 'gpkg' || $file_extension == 'geopkg') {

                        $this->convertAndSaveLayers($gdal_path, $filePath, $file_extension, $userId, $upload_limit);
                        Storage::delete([$filePath]); # Delete the original dpkg file because it's smelly

                    } elseif ($file_extension == 'pbf') {

                        $this->convertAndSaveLayers($gdal_path, $filePath, $file_extension, $userId, $upload_limit);
                        Storage::delete([$filePath]); # Delete the original dpkg file because it's smelly

                    } elseif ($file_extension == 'shp') { # TODO: We need to change upload to allow more than one file to support this because it needs a shx and shp file to work

                        $this->convertAndSaveLayers($gdal_path, $filePath, $file_extension, $userId, $upload_limit);
                        Storage::delete([$filePath]); # Delete the original dpkg file because it's smelly

                    } else {
                        Storage::delete([$filePath]);
                        return back()->withErrors(['my_file' => 'This file type is not supported.']);
                    }

                } catch (\RuntimeException $e) {
                    return back()->withErrors(['my_file' => $e->getMessage()]);
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

