<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    function processDeepArray(array $array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // If the value is an array, recursively call the function
                echo "Entering level for key: " . $key . "\n";
                processDeepArray($value);
                echo "Exiting level for key: " . $key . "\n";
            } else {
                // Process the non-array element
                echo "Key: " . $key . ", Value: " . $value . "\n";
            }
        }
    }

    public function blank()
    {
        $userId = auth()->id(); // Assuming authenticated user
        $path = "users/{$userId}";
        $files = Storage::files($path);
        $coord_array = [];
        $count = 0;
        foreach ($files as $file) {
            $read_file = Storage::get($file);
            $json_decode = json_decode($read_file, true); // 'true' decodes to an associative array
            $name = $json_decode['name'];
            $features_array = $json_decode['features'];
            foreach ($features_array as $feature) {
                $stack = [$feature['geometry']['coordinates']];
                while (!empty($stack)) {
                        $current = array_pop($stack); // Get the last element from the stack (current array level)
                        $temp_array = [];
                        foreach ($current as $key => $value) {
                            if (is_array($value)) {
                                // If the value is an array, push it onto the stack to process later
                                array_push($stack, $value);
                            } else {
                                // If it's a scalar value, process it (e.g., print it)
                                #echo "Key: $key, Value: $value\n";
                                if ($key == 0) {
                                    $temp_array['long'] = $value;
                                } else {
                                    $temp_array['lat'] = $value;
                                    $coord_array[$count][] = $temp_array;
                                }
                            }
                        }
                }
            }
            $count = $count + 1;
        }

        $array = ['text'=> 'farts smell', 'maps' => $coord_array];
        return view('layouts.blank-page', $array);
    }
}
