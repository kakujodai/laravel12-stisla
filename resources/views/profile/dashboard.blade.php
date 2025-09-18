@extends('layouts.app')

@section('title', $dashboard_info['name'])

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" integrity="sha512-ELV+xyi8IhEApPS/pSj66+Jiw+sOT1Mqkzlh8ExXihe4zfqbWkxPRi8wptXIO9g73FSlhmquFlUOuMSoXz5IRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- CSS Libraries -->
@endpush

@section('content')
	<div class="row mb-3">
		<div class="col-md-12 flex-header">
			<h3>{{ $dashboard_info['name'] }}</h3>
			<a href="{{ route('profile.add-widgets', ['id' => $dashboard_info['id']]) }}" class="btn btn-primary float-right">Add Widget</a>
		</div>
	</div>
    <div id="none_shall_pass" class="">
        <div class="row">
			<!-- Our Dependencies for just leaflet maps -->
			<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
	    	<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-ajax/2.1.0/leaflet.ajax.min.js" integrity="sha512-Abr21JO2YqcJ03XGZRPuZSWKBhJpUAR6+2wH5zBeO4wAw4oksr8PRdF+BKIRsxvCdq+Mv4670rZ+dLnIyabbGw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	    	<script src="https://makinacorpus.github.io/Leaflet.Spin/docs/spin/dist/spin.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	    	<script src="https://cdnjs.cloudflare.com/ajax/libs/Leaflet.Spin/1.1.2/leaflet.spin.js" integrity="sha512-em8lnJhVwVTWfz2Qg/1hRvLz6gTM4RiXs5gTywZMz/NNunZUybf1PsYHKAjcdx2/+zdRwU4PzOM9CwC5o2ko0g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
                    <div id="sortable-cards{{ $widget['id'] }}" class="card">
                        <div class="card-header flex-header">
							{{$widget['name']}}
							<form action="{{ route('profile.delete-widget', ['id' => $widget['id'], 'dash_id' => $dashboard_info['id']]) }}" method="POST" style="display: inline-block;">
								@csrf
								<button type="submit" class="btn btn-secondary rounded-sm fas fa-trash"></button>
							</form>
						</div>
                        <div class="card-body" style="height:400px;">
			    			<div class="no-sort" id="{{ $widget['random_id'] }}" style="height:100%;"></div>
							<script>
								const overlayMaps{{ $widget['random_id'] }} = {};
								// Generate all geojson overlays
								@foreach ($all_geojsons as $each_geojson)
									var {{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}= L.geoJson.ajax();
									overlayMaps{{ $widget['random_id'] }}.{{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }} = {{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }};
								@endforeach
								// Setup the map layers we want to use
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
								var osmwmsLayer = L.tileLayer.wms('http://ows.mundialis.de/services/service?', {
									layers: 'OSM-WMS'
								})
								var topowmsLayer = L.tileLayer.wms('http://ows.mundialis.de/services/service?', {
									layers: 'TOPO-WMS'
								})
								var baseMaps = {
									"OpenStreet": osm,
									"OpenStreet Topographic": osmT,
									"OpenStreet Humanitarian": osmH,
									"Open Street Map OSM-WMS": osmwmsLayer,
									"Open Street Map TOPO-WMS": topowmsLayer,
								};

								// Lazy load the geojson assigned to this widget
								var {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }} = new L.GeoJSON.AJAX("{{ route('profile.get-geojson', ['filename' => pathinfo($widget['filename'], PATHINFO_FILENAME)]) }}", {
									oneachfeature: function (feature, layer) {
										layer.bindPopup('<pre>'+json.stringify(feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
									}
								});
								// Update the object we created earlier with an empty geojson, with the newly loaded ajax driven pull above
								overlayMaps{{ $widget['random_id'] }}.{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }} = {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }};

								// Instantiate the map with the map layer, and our default geojson 
								var map{{ $widget['random_id'] }} = L.map('{{ $widget['random_id'] }}', {
									center: [0,0],
									zoom: 14,
									layers: [osm, {{ str_replace('-', '', pathinfo($widget['filename'], PATHINFO_FILENAME)); }}{{ $widget['random_id'] }}]
								});
								const resizeObserver{{ $widget['random_id'] }} = new ResizeObserver(() => {
									map{{ $widget['random_id'] }}.invalidateSize();
								});
								resizeObserver{{ $widget['random_id'] }}.observe(document.getElementById("{{ $widget['random_id'] }}"));
								// Create the layers for display in the interface (top right icon)
								var layerControl = L.control.layers(baseMaps, overlayMaps{{ $widget['random_id'] }}).addTo(map{{ $widget['random_id'] }});	
								L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
									attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
									id: 'mapbox/streets-v11',
								}).addTo(map{{ $widget['random_id'] }});

								// Use GeoJson to center map AFTER the ajax call to get the geojson
								{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}.on('data:loaded', function() {
									//Calculate the bounds of the geojson to target the map
									var bounds = {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}.getBounds();
									// Bind them to the map as a whole, so the map and geojson adjust
									map{{ $widget['random_id'] }}.fitBounds(bounds);
									// Add popup info for each layer that includes all values in the geojson properties
									{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}.eachLayer(function (layer) {
										layer.bindPopup('<pre>'+JSON.stringify(layer.feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
									});
								});
								// Create a trigger when we add a new geojson overlay
								map{{ $widget['random_id'] }}.on('overlayadd', onOverlayAdd);
								// Function for the trigger above that hands us the overlay name that appears in the dropdown. Use this to query for the json data
								function onOverlayAdd(e) {
									// Add a spinner to the map while we wait
									map{{ $widget['random_id'] }}.spin(true);
									var full_name = e.name;
									// Pull geojson using the getJsonFromServer function above and add it to the overlayMap object for the correct key
									getJsonFromServer(`/profile/get-geojson/${full_name}`)
										.then(data => { // We got the data without issue
											if (data) {
												// Adding the data. Have to segregate the overlayMaps from each map so they don't modify each other
												overlayMaps{{ $widget['random_id'] }}[full_name].addData(data);
												// Newly added layers have their popups added to the map using eachLayer instead of oneachfeature
												overlayMaps{{ $widget['random_id'] }}[full_name].eachLayer(function (layer) {
													layer.bindPopup('<pre>'+JSON.stringify(layer.feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
												});
												map{{ $widget['random_id'] }}.spin(false); // Turn off the spin
											} else { // We failed... TODO: make this appear on screen to the user so they refresh
												console.log("Failed to get json");
											}
										});
								}
							</script>
                        </div>
                    </div>
                </div>
				@else
					@if ($widget['widget_type_id'] == 4) <!-- I'm a wide chart -->
					<div class="col-md-3">
					@else
					<div class="col-md-6">
					@endif
						<div id="sortable-cards{{ $widget['id'] }}" class="card">
							<div class="card-header flex-header">
								{{$widget['name']}}
								<form action="{{ route('profile.delete-widget', ['id' => $widget['id'], 'dash_id' => $dashboard_info['id']]) }}" method="POST" style="display: inline-block;">
									@csrf
									<button type="submit" class="btn btn-secondary rounded-sm fas fa-trash"></button>
								</form>
							</div>
							<div class="card-body">
								<div class="no-sort" style="height: 100%; width: 100%;">
									@if (!$widget['chart'])
										<b>Failed to produce results with selected parameters.</b>
									@else
									<x-chartjs-component :chart="$widget['chart']" />
									@endif
								</div>
							</div>
						</div>
					</div>				
                @endif
            @endforeach
        </div>
    </div>
	<script>
		// That sweet sweet moving cards trick
		document.addEventListener("DOMContentLoaded", function() {
			$(".card").draggable({
				addClasses: false,
				cursor: "grabbing", // Change the cursor to the move symbol
				stack: ".card", // What items to change z-index on so they don't interact
				containment: "#none_shall_pass", // Make sure you can't put the card outside of this div
				cancel: ".no-sort", // Make sure we don't attempt to move the card when we're actually in the map panning
				stop: function(event, ui) {
					var positions = JSON.parse(localStorage.positions || "{}");
					positions[this.id] = ui.position; // Store by element ID
					localStorage.positions = JSON.stringify(positions);
				}
			}).resizable({
				stop: function(event, ui) {
					var positions = JSON.parse(localStorage.positions || "{}");
					positions[this.id] = ui.position; // Store by element ID
					positions[this.id].width = ui.size.width;
					positions[this.id].height = ui.size.height;
					localStorage.positions = JSON.stringify(positions);
				}
			});
			var sPositions = localStorage.positions || "{}";
			var positions = JSON.parse(sPositions);
			// If using localStorage
			$.each(positions, function(id, pos) {
				$("#" + id).css(pos);
			});
		});
	</script>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8"></script>
	<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@next"></script>
    <!-- Page Specific JS File -->
@endpush
