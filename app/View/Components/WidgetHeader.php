<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class WidgetHeader extends Component
{
    
    public $name;
    public $widgetId;
    public $dashboardId;
    public $randomId;
    public $widgetTypeId;
    public $hasSettings;
    /* Create a new component instance. */
    public function __construct($name, $widgetId, $dashboardId, $widgetTypeId, $randomId = null, $hasSettings = false)
    {
        $this->name = $name;
        $this->widgetId = $widgetId;
        $this->dashboardId = $dashboardId;
        $this->widgetTypeId = $widgetTypeId;
        $this->randomId = $randomId;
        $this->hasSettings = $hasSettings;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.widget-header');
    }
}
