<?php

namespace App\Http\Controllers;
use App\Models\Dashboard;
use Illuminate\Support\Facades\Auth;

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
        $my_dashboards = Dashboard::where('user_id', '=', Auth::id())->get();
        return view('home', ['dashboards' => $my_dashboards]);
    }

    public function blank()
    {
        return view('profile.blank-page');
    }
}
