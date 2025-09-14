<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; //for storage::file()
use Illuminate\Support\Str; //for Str::random()
use App\Models\FileUpload;
use IcehouseVentures\LaravelChartjs\Facades\Chartjs;


class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Profil Berhasil Di Update!');
    }

    public function changepassword()
    {
        return view('profile.changepassword', ['user' => Auth::user()]);
    }

    public function password(Request $request)
    {

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password Sebelumnya Salah!']);
        }

        $user->fill([
            'password' => Hash::make($request->new_password),
        ])->save();

        return back()->with('status', 'Password berhasil Diubah!');

    }

    public function show_files() {
        $my_files = FileUpload::where('user_id', '=', Auth::id())->get();
        return view('profile.upload', ['files' => $my_files]);
    }

    public function blank()
    {
        $userId = auth()->id(); // Assuming authenticated user
        $path = "users/{$userId}";
        $files = Storage::files($path);
        $geojson_array = [];
        $count = 0;
        $geojson_chart_array = [];
        foreach ($files as $file) {
            $read_file = Storage::get($file); //laravel's storage facade Storage::get($file)
            $json_version = json_decode($read_file, true);
            #var_dump($json_version['name']);
            #die;
            foreach ($json_version['features'] as $feature) {
                foreach ($feature['properties'] as $key => $value) {
                    if (is_int($value)) {
                        $geojson_chart_array[$key][] = $value;
                    }
                }
            }
            $geojson_array[] = ['id' => Str::random(), 'json' => $read_file, 'filename' => basename($file)]; //$geojson_array[] = geojson_array.append()
            $count = $count + 1;
        }

        //line chart example
        $line_charts = []; //array to hold line charts
        # Gen Labels
        $chart_card_labels = []; //label for each chart card
        foreach ($geojson_chart_array as $key => $value)  {  //iterate over key value pairs in geojson_chart_array
            $labels = []; //labels for x axis
            for ($i = 0; $i < count($value); $i++) { //loop through number of values in each key
                $labels[] = "Value$i"; //append ith value to labels array
            }
            $chart_card_labels[] = $key; //chart card label is the key name
            $line_charts[] = Chartjs::build() //makes empty chart
                ->name($key) //fill da chart!!!! with what we just did!!!!!!!
                ->type('line')
                ->size(['width' => 400, 'height' => 200]) 
                ->labels($labels)
                ->datasets([
                    [
                        "label" => $key,
                        "data" => $value,
                        "fill" => false, //calculus (y/n)
                        "pointRadius" => 0,
                        "borderWidth" => 1,
                    ]
                ])
                ->options([
                    "interaction" =>[
                        "mode" => "nearest", // or 'index'
                        "intersect" => false
                    ],
                    "hover" => [
                        "mode" => "nearest", // or 'index'
                        "intersect" => false
                    ]
                ]);
                //->options([]);
                // ->options([
                //     "scales" => [
                //         "y" => [
                //             "beginAtZero" => true
                //             ]
                //         ]
                // ]);
        }
        $array = ['text'=> 'farts smell', 'maps' => $geojson_array, 'charts' => $line_charts, 'chart_card_labels' => $chart_card_labels];
        return view('profile.blank-page', $array);
    }
    
}
