<?php

namespace App\Http\Controllers;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $get_widgets = DashboardWidget::select('dashboard_id', DB::raw('count(*) as d_count'))
            ->where('user_id', '=', Auth::id())
            ->groupBy('dashboard_id')
            ->get();
        $widget_counts = [];
        foreach($get_widgets as $widget) {
            $widget_counts[$widget['dashboard_id']] = $widget['d_count'];
        }
        return view('home', ['dashboards' => $my_dashboards, 'widget_counts' => $widget_counts]);
    }

    public function blank()
    {
        return view('profile.blank-page');
    }
}
