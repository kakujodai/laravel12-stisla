@extends('layouts.app')

@section('title', 'Blank Page')
@php
    $chart_count = 0;
@endphp

@push('style')
    <!-- CSS Libraries -->
@endpush

@section('content')
    <div class="main-content">
        <div class="row">
            @foreach ($maps as $map)
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">{{ $map['filename'] }}</div>
                    <div class="card-body">
                        <x-maps-leaflet :mapId="$map['id']" :geoJson="$map['json']"></x-maps-leaflet>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="row">
            @foreach ($charts as $chart)
            <div class="col-md-6"> 
                <div class="card">
                    <div class="card-header">chart-{{$chart_count}}</div>
                    <div id="chart-$chart_count" class="card-body">
                        <x-chartjs-component :chart="$chart" />
                    </div>
                </div>
            </div>
            @php
                $chart_count = $chart_count + 1;
            @endphp
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script>
    <!-- Page Specific JS File -->
@endpush
