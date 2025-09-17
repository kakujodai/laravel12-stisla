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
	    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-ajax/2.1.0/leaflet.ajax.min.js" integrity="sha512-Abr21JO2YqcJ03XGZRPuZSWKBhJpUAR6+2wH5zBeO4wAw4oksr8PRdF+BKIRsxvCdq+Mv4670rZ+dLnIyabbGw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
			<script>
				async function getJsonFromServer(url) {
				  try {
				    const response = await fetch(url); // Make the network request
				    
				    if (!response.ok) { // Check if the request was successful
				      throw new Error(`HTTP error! status: ${response.status}`);
				    }
				    
				    const jsonData = await response.json(); // Parse the response as JSON
				    return jsonData;
				  } catch (error) {
				    console.error("Error fetching JSON:", error);
				    return null; // Or handle the error as appropriate
				  }
				}
			</script>
            @foreach ($widgets as $widget)
                @if ($widget['widget_type_id'] == 1) <!-- I'm the map, i'm the map (he's the map, he's the map) I'M THE MAP!-->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header flex-header">
							{{$widget['name']}}
							<form action="{{ route('profile.delete-widget', ['id' => $widget['id'], 'dash_id' => $dashboard_info['id']]) }}" method="POST" style="display: inline-block;">
								@csrf
								<button type="submit" class="btn btn-secondary rounded-sm fas fa-trash"></button>
							</form>
						</div>
                        <div class="card-body">
			    			<div id="{{ $widget['random_id'] }}" style="height:400px;"></div>
							<script>
								const overlayMaps{{ $widget['random_id'] }} = {};
								// Generate all geojson overlays
								@foreach ($all_geojsons as $each_geojson)
									var {{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}= L.geoJson.ajax();
									overlayMaps{{ $widget['random_id'] }}.{{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }} = {{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }};
								@endforeach
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
								var {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }} = new L.GeoJSON.AJAX("{{ route('profile.get-geojson', ['filename' => pathinfo($widget['filename'], PATHINFO_FILENAME)]) }}", {
                                                oneachfeature: function (feature, layer) {
                                                        layer.bindpopup('<pre>'+json.stringify(feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
                                                }
});
								overlayMaps{{ $widget['random_id'] }}.{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }} = {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }};
								var map{{ $widget['random_id'] }} = L.map('{{ $widget['random_id'] }}', {
									center: [0,0],
									zoom: 14,
									layers: [osm, {{ str_replace('-', '', pathinfo($widget['filename'], PATHINFO_FILENAME)); }}{{ $widget['random_id'] }}]
								});
								var layerControl = L.control.layers(baseMaps, overlayMaps{{ $widget['random_id'] }}).addTo(map{{ $widget['random_id'] }});	
								L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
									attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
									id: 'mapbox/streets-v11',
								}).addTo(map{{ $widget['random_id'] }});

								// Use GeoJson to center map AFTER the ajax call to get the geojson
								{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}.on('data:loaded', function() {
									var bounds = {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}.getBounds();
									console.log(bounds);
									map{{ $widget['random_id'] }}.fitBounds(bounds);
								});
								map{{ $widget['random_id'] }}.on('overlayadd', onOverlayAdd);
								function onOverlayAdd(e) {
									console.log(e);
									var full_name = e.name;
									getJsonFromServer(`/profile/get-geojson/${full_name}`)
										.then(data => {
										  if (data) {
											console.log(overlayMaps{{ $widget['random_id'] }});	
											overlayMaps{{ $widget['random_id'] }}[full_name].addData(data);
										  } else {
											console.log("Failed to get json");
										  }
									});
									// var load_layer = new L.GeoJSON.AJAX(`/profile/get-geojson/${full_name}`, {
				                                        //        oneachfeature: function (feature, layer) {
                                				        //                layer.bindpopup('<pre>'+json.stringify(feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
				                                        //        }
									//});
								}
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
