@extends('layouts.app')

@section('title', $dashboard_info['name'])

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- CSS Libraries -->
@endpush

@section('content')
    <div class="main-content">
        <div class="row mb-3">
            <div class="col-md-12">
                <a href="{{ route('profile.add-widgets', ['id' => $dashboard_info['id']]) }}" class="btn btn-primary float-right">Add Widget</a>
            </div>
        </div>
        <div class="row">
    	    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
	    <script>
	       const overlayMaps = {};
               // Generate all geojson overlays
	       @foreach ($all_geojsons as $each_geojson)
	          var {{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }} = L.geoJson({!! $each_geojson['geojson'] !!}, {
			 onEachFeature: function (feature, layer) {
			   layer.bindPopup('<pre>'+JSON.stringify(feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
			 }
		  });
	          overlayMaps.{{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }} = {{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }};
	       @endforeach
            </script>
            @foreach ($widgets as $widget)
                @if ($widget['widget_type_id'] == 1)
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">{{$widget['name']}}</div>
                        <div class="card-body">
			    			<div id="{{ $widget['random_id'] }}" style="height:400px;"></div>
							<script>
								var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
									maxZoom: 19,
									attribution: '© OpenStreetMap'
								});
								var osmH = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
									maxZoom: 19,
									attribution: '© OpenStreetMap contributors, Tiles style by Humanitarian OpenStreetMap Team hosted by OpenStreetMap France'
								});
								var osmT = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
									maxZoom: 19,
									attribution: 'Map data: © OpenStreetMap contributors, SRTM | Map style: © OpenTopoMap (CC-BY-SA)'
								});
								var baseMaps = {
									"OpenStreet": osm,
									"OpenStreet Topographic": osmT,
									"OpenStreet Humanitarian": osmH
								};
								var origGeoJsonData = {};
								origGeoJsonData.{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }} = {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }};
								var map{{ $widget['random_id'] }} = L.map('{{ $widget['random_id'] }}', {
									center: [0, 0],
									zoom: 14,
									layers: [osm, {{ str_replace('-', '', pathinfo($widget['filename'], PATHINFO_FILENAME)); }}]
								});
								var layerControl = L.control.layers(baseMaps, overlayMaps).addTo(map{{ $widget['random_id'] }});	
								L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
									attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
									id: 'mapbox/streets-v11',
								}).addTo(map{{ $widget['random_id'] }});

								// Use GeoJson to center map
								var bounds = {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}.getBounds();
								map{{ $widget['random_id'] }}.fitBounds(bounds);
							</script>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script>
    <!-- Page Specific JS File -->
@endpush
