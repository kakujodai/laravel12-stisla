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

        if (!FileUpload::where('filename', '=', $filename)->where('user_id', '=', $userId)->exists()) {
            if (Storage::putFileAs($path, $file, $filename)) {
                $filePath = storage_path("app/$path/$filename");
                # If we placed the file successfully
                $file_upload = new FileUpload;
                $file_upload->user_id = $userId;
                $file_upload->filename = $filename;
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