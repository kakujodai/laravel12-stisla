<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Shared Dashboard')</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">

    <style>
        html,
        body {
            min-height: 100%;
            background: #1f222b !important;
            color: #e4e6eb !important;
        }

        body {
            margin: 0;
            overflow-x: hidden;
        }

        .public-dashboard-wrapper {
            min-height: 100vh;
            padding: 24px;
            background: #1f222b;
        }

        .public-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .dashboard-canvas,
        #dashboard-canvas {
            min-height: calc(100vh - 120px);
            background: #252934 !important;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 16px;
            padding: 20px;
            overflow: auto;
        }

        .dashboard-widget,
        .widget-card,
        .card {
            background: #2b2f3a !important;
            color: #e4e6eb !important;
            border: 1px solid rgba(255,255,255,.08) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,.25);
        }

        .card-header {
            background: #2b2f3a !important;
            color: #e4e6eb !important;
            border-bottom: 1px solid rgba(255,255,255,.08) !important;
        }

        .table {
            color: #e4e6eb !important;
            margin-bottom: 0;
        }

        .table thead th {
            background: #f5f5f5;
            color: #333;
        }

        .table tbody tr,
        .table tbody td {
            background: #252934 !important;
            color: #e4e6eb !important;
        }

        .dashboard-locked .ui-resizable-handle,
        .dashboard-locked .widget-settings,
        .dashboard-locked .dropdown,
        .dashboard-locked .btn-widget-settings {
            display: none !important;
        }
    </style>

    @stack('css')
</head>
<body>
    <div class="public-dashboard-wrapper">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
