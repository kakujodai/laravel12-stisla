<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\DashboardWidgetType;

class WidgetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            ['name' => 'Map',],
            ['name' => 'Line Chart',],
            ['name' => 'Bar Chart',],
            ['name' => 'Pie Chart',],
        ];
        DashboardWidgetType::insert($records);
    }
}
