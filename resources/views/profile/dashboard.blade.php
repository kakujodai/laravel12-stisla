@extends('layouts.app')

@section('title', $dashboard_info['name'])

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" integrity="sha512-ELV+xyi8IhEApPS/pSj66+Jiw+sOT1Mqkzlh8ExXihe4zfqbWkxPRi8wptXIO9g73FSlhmquFlUOuMSoXz5IRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- CSS Libraries -->
	<style>
		/* Style the select2 box to match requested UI: bordered container, tags on top, options below */
		.col-selector-wrap .select2-container--default .select2-selection--multiple {
			border: none; /* container border handled by wrapper */
			padding: 6px 6px 2px 6px;
			min-height: 44px;
			box-shadow: none;
		}
		.col-selector-wrap .select2-container--default .select2-selection__rendered {
			display: flex;
			flex-wrap: wrap;
			gap: 6px;
			align-items: center;
		}
		.col-selector-wrap .select2-container--default .select2-selection__choice {
			background:#ececec;
			border:1px solid #c9c9c9;
			color:#222;
      		padding:4px 8px;
			border-radius:4px;
			margin:0;
			font-weight:600;
			letter-spacing:.2px;
		}
		.col-selector-wrap .select2-dropdown {
			border-top: 1px solid #e2e2e2;
			box-shadow: none;
			border-radius: 0 0 6px 6px;
		}
		/* Ensure the dropdown fills the wrapper width */
		.col-selector-wrap .select2-container .select2-dropdown--below {
			width: 100% !important;
		}
		/* Increase list item padding to match screenshots */
		.col-selector-wrap .select2-results__option {
			padding: 10px 12px;
			font-weight: 600;
		}
		.col-selector-wrap .select2-container--default .select2-selection--multiple {
			border: 1px solid #000;
			border-radius: 6px;
			padding: 6px 6px 2px 6px;
			min-height: 44px;
			box-shadow: none;
		}
	</style>
@endpush

@section('content')
	<div class="row mb-3">
		<div class="col-md-12 flex-header">
			<h3>{{ $dashboard_info['name'] }}</h3>
			<a href="{{ route('profile.add-widgets', ['id' => $dashboard_info['id']]) }}" class="btn btn-primary float-right">Add Widget</a>
		</div>
		<div class="col-md-12 flex-header">
			<button type="button" class="btn btn-secondary float-right" id="dashboardLockBtn"> <i class="fas fa-lock"></i> Lock Dashboard</button>
		</div>
	</div>
    <div id="none_shall_pass" class="">
        <div class="row">
			<!-- Our Dependencies for just leaflet maps -->
			<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
	    	<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-ajax/2.1.0/leaflet.ajax.min.js" integrity="sha512-Abr21JO2YqcJ03XGZRPuZSWKBhJpUAR6+2wH5zBeO4wAw4oksr8PRdF+BKIRsxvCdq+Mv4670rZ+dLnIyabbGw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	    	<script src="https://makinacorpus.github.io/Leaflet.Spin/docs/spin/dist/spin.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	    	<script src="https://cdnjs.cloudflare.com/ajax/libs/Leaflet.Spin/1.1.2/leaflet.spin.js" integrity="sha512-em8lnJhVwVTWfz2Qg/1hRvLz6gTM4RiXs5gTywZMz/NNunZUybf1PsYHKAjcdx2/+zdRwU4PzOM9CwC5o2ko0g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js" integrity="sha512-TiMWaqipFi2Vqt4ugRzsF8oRoGFlFFuqIi30FFxEPNw58Ov9mOy6LgC05ysfkxwLE0xVeZtmr92wVg9siAFRWA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

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
			<script>
				// global map event bus
				window.MapBus = new EventTarget();
				function mapGetBBox(map) {
    				var b = map.getBounds();
    				return {
    	    			south: b.getSouthWest().lat,
    	    			west:  b.getSouthWest().lng,
    	    			north: b.getNorthEast().lat,
    	    			east:  b.getNorthEast().lng
    				};
				}
				// tiny debounce so we don't spam updates during pans
				function debounce(fn, ms) {
					let t;
					return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
				}
			</script>

            @foreach ($widgets as $widget)
                @if ($widget['widget_type_id'] == 1) <!-- I'm the map, i'm the map (he's the map, he's the map) I'M THE MAP!-->
                <div class="col-md-4">
                    <div id="sortable-cards{{ $widget['id'] }}" class="card">
                        <x-widget-header 
   							:name="$widget['name']" 
						    :widget-id="$widget['id']" 
						    :dashboard-id="$dashboard_info['id']" 
							:random-id="$widget['random_id']"
							:widget-type-id="$widget['widget_type_id']" 
							:has-settings="true"
						/>
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
								var osmwmsLayer = L.tileLayer.wms('http://ows.mundialis.de/services/service?', { layers: 'OSM-WMS' });
								var topowmsLayer = L.tileLayer.wms('http://ows.mundialis.de/services/service?', { layers: 'TOPO-WMS' });

								var baseMaps = {
									"OpenStreet": osm,
									"OpenStreet Topographic": osmT,
									"OpenStreet Humanitarian": osmH,
									"Open Street Map OSM-WMS": osmwmsLayer,
									"Open Street Map TOPO-WMS": topowmsLayer,
								};

								// Create circleMarkers
								function createCircleMarker (feature, latlng) {
    								var color = feature.properties.color || '#3388ff';
    								return L.circleMarker(latlng, {
        								radius: 3,
        								color: color,
        								fillColor: color,
        								weight: 1,
        								fillOpacity: 0.8
    								});
								}

								// Lazy load the geojson assigned to this widget
								var {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }} =
  									new L.GeoJSON.AJAX("{{ route('profile.get-geojson', ['filename' => pathinfo($widget['filename'], PATHINFO_FILENAME)]) }}", {
    									pointToLayer: createCircleMarker,
										onEachFeature: function (feature, layer) {
      										layer.bindPopup('<pre>' + JSON.stringify(feature.properties, null, ' ').replace(/[\{\}"]/g, '') + '</pre>');
    									}
  									});

								overlayMaps{{ $widget['random_id'] }}.{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }} = {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }};

								// Instantiate the map with the base layer and our default geojson 
								var map{{ $widget['random_id'] }} = L.map('{{ $widget['random_id'] }}', {
									center: [0,0],
									zoom: 14,
									layers: [osm, {{ str_replace('-', '', pathinfo($widget['filename'], PATHINFO_FILENAME)); }}{{ $widget['random_id'] }}]
								});

								// ===== per-map storage key (tie to dashboard + widget)
								const viewKey{{ $widget['random_id'] }} = 'mapview:dash{{ $dashboard_info["id"] }}:widget{{ $widget["id"] }}';

  								function saveMapView{{ $widget['random_id'] }}() {
    								const c = map{{ $widget['random_id'] }}.getCenter();
    								const z = map{{ $widget['random_id'] }}.getZoom();
    								localStorage.setItem(viewKey{{ $widget['random_id'] }}, JSON.stringify({ lat: c.lat, lng: c.lng, zoom: z }));
  								}
								function restoreMapView{{ $widget['random_id'] }}() {
    								const raw = localStorage.getItem(viewKey{{ $widget['random_id'] }});
    								if (!raw) return null;
    								try { return JSON.parse(raw); } catch { return null; }
  								}

								function broadcastBBox() {
    								const bbox = mapGetBBox(map{{ $widget['random_id'] }});
    								window.MapBus.dispatchEvent(new CustomEvent('map:bbox', {
        								detail: {
            								bbox,
            								sourceWidgetId: '{{ $widget['id'] }}',
            								sourceFilename: '{{ pathinfo($widget['filename'], PATHINFO_FILENAME) }}'
        									}
    								}));
								}

								// Debounce the pan/zoom broadcasts
								var debouncedBroadcast{{ $widget['random_id'] }} = debounce(broadcastBBox, 200);
								// Don't persist map view until GeoJSON data has loaded — avoids saving transient 0,0 from resizes
								let mapDataLoaded{{ $widget['random_id'] }} = false;
								map{{ $widget['random_id'] }}.on('moveend zoomend', () => {
									if (!mapDataLoaded{{ $widget['random_id'] }}) return;
									saveMapView{{ $widget['random_id'] }}(); // persist on every move/zoom
									debouncedBroadcast{{ $widget['random_id'] }}();
								});

								// After the GeoJSON loads, fit/restore and broadcast once
								{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}.on('data:loaded', function () {
									var bounds = {{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}.getBounds();

									const v = restoreMapView{{ $widget['random_id'] }}();
									if (v && Number.isFinite(v.lat) && Number.isFinite(v.lng) && Number.isFinite(v.zoom)) {
										map{{ $widget['random_id'] }}.setView([v.lat, v.lng], v.zoom);
									} else {
										map{{ $widget['random_id'] }}.fitBounds(bounds);
										saveMapView{{ $widget['random_id'] }}();
									}

									{{ pathinfo($widget['filename'], PATHINFO_FILENAME); }}{{ $widget['random_id'] }}.eachLayer(function (layer) {
										layer.bindPopup('<pre>'+JSON.stringify(layer.feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
									});

									// broadcast once
									broadcastBBox();
									// Mark the map as loaded so user interactions save correctly
									mapDataLoaded{{ $widget['random_id'] }} = true;
								});

								const resizeObserver{{ $widget['random_id'] }} = new ResizeObserver(() => {
									map{{ $widget['random_id'] }}.invalidateSize();
								});
								resizeObserver{{ $widget['random_id'] }}.observe(document.getElementById("{{ $widget['random_id'] }}"));
                                
								// Layers UI
								var layerControl = L.control.layers(baseMaps, overlayMaps{{ $widget['random_id'] }}).addTo(map{{ $widget['random_id'] }});	
								L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
									attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
									id: 'mapbox/streets-v11',
								}).addTo(map{{ $widget['random_id'] }});

								// Load additional overlays on demand
								map{{ $widget['random_id'] }}.on('overlayadd', onOverlayAdd);
								function onOverlayAdd(e) {
									map{{ $widget['random_id'] }}.spin(true);
									var full_name = e.name;
									getJsonFromServer(`/profile/get-geojson/${full_name}`)
										.then(data => {
											if (data) {
												overlayMaps{{ $widget['random_id'] }}[full_name].addData(data);
												overlayMaps{{ $widget['random_id'] }}[full_name].eachLayer(function (layer) {
													layer.bindPopup('<pre>'+JSON.stringify(layer.feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
												});
												map{{ $widget['random_id'] }}.spin(false);
											} else {
												console.log("Failed to get json");
											}
										});
								}
							</script>
                        </div>
                    </div>
                </div>

				@elseif ($widget['widget_type_id'] > 1 && $widget['widget_type_id'] <= 4) <!-- Charts -->
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
									<div class="no-sort chart-widget"
										data-widget-id="{{ $widget['id'] }}"
										data-map-link-id="{{ $widget['map_link_id'] ?? 'noLink321π' }}"
										style="height: 100%; width: 100%;">
									@if (!$widget['chart'])
										<b>Failed to produce results with selected parameters.</b>
									@else
										<x-chartjs-component :chart="$widget['chart']" />
									@endif
									</div>
								</div>
							</div>
						</div>

					@else <!-- Table -->
						<div  class="col-md-12">
							<div id="no-resize" class="card">
								<div class="card-header">
									<div class="row">
										<div class="col-sm-10 text-left">
											<h2 class="card-title">{{$widget['name']}}</h2>
										</div>
										<div class="col-sm-2">
											<div class="btn-group float-right ms-2">
												<form id="delete-{{ $widget['id'] }}" action="{{ route('profile.delete-widget', ['id' => $widget['id'], 'dash_id' => $dashboard_info['id']]) }}" method="POST" style="display: inline-block;">
													@csrf
													<button type="submit" class="btn btn-sm btn-warning rounded-sm fas fa-trash"></button>
												</form>
											</div>
										</div>
									</div>
								</div>

								{{-- Column selector UI (styled box with Select2) --}}
								<div class="px-3 pb-2">
									<div id="colWrap-{{ $widget['random_id'] }}" class="col-selector-wrap" style="border:1px solid #ddd;border-radius:6px;padding:10px;">
										<label class="mb-2" style="font-weight:600;color:#2c3e50;">Select the properties you want to appear on the table</label>
										<select id="colSelect-{{ $widget['random_id'] }}" class="form-control column-selector" multiple="multiple" style="width:100%" data-server-selected='@json($widget['visible_columns'] ?? [])'>
											@foreach($widget['table_headings'] as $i => $heading)
												@php $isSelected = empty($widget['visible_columns']) || in_array($heading, $widget['visible_columns']); @endphp
												<option value="{{ $heading }}" {{ $isSelected ? 'selected' : '' }}>{{ $heading }}</option>
											@endforeach
										</select>
									</div>
								</div>

								<div class="card-body">
									<div class="table-responsive">
										<table id="table-{{ $widget['random_id'] }}" class="table table-striped" style="z-index: 10;">
											<thead>
												<tr>
													@foreach ($widget['table_headings'] as $heading)
													<th>{{ $heading }}</th>
													@endforeach
												</tr>
											</thead>
											<tbody>
												@foreach ($widget['table'] as $key)
												<tr>
													@foreach($key as $value)
														<td>{{ $value }}</td>
													@endforeach
												</tr>
												@endforeach
											</tbody>
										</table>
										<script>
											document.addEventListener("DOMContentLoaded", function() {
												const tableEl = $("#table-{{ $widget['random_id'] }}");
												// Initialize DataTable if not already
												const table = tableEl.DataTable();
												const select = $("#colSelect-{{ $widget['random_id'] }}");
												// gather header names in order
												const headers = tableEl.find('thead th').map(function(){ return $(this).text().trim(); }).get();
												// Initialize Select2 on the column selector
												if (typeof select.select2 === 'function') {
													select.select2({
														placeholder: 'Columns',
														width: 'resolve',
														closeOnSelect: false,
														dropdownParent: $('#colWrap-{{ $widget['random_id'] }}')
													});
												}
												const storeKey = 'tableCols-{{ $widget['random_id'] }}';
												function applyVisibilityFromSelected(selected){
													const s = new Set((selected || []).map(String));
													headers.forEach(function(header, idx){
														const visible = s.has(header);
														table.column(idx).visible(visible, false);
													});
													table.draw(false);
												}
												// Restore from server (preferred) or localStorage
												try {
													const serverSelRaw = select.attr('data-server-selected') || '[]';
													const serverSel = JSON.parse(serverSelRaw);
													if (Array.isArray(serverSel) && serverSel.length > 0) {
														select.val(serverSel).trigger('change.select2');
														applyVisibilityFromSelected(serverSel);
													} else {
														const stored = JSON.parse(localStorage.getItem(storeKey) || 'null');
														if (Array.isArray(stored)) {
															select.val(stored).trigger('change.select2');
															applyVisibilityFromSelected(stored);
														} else {
															applyVisibilityFromSelected(select.val() || []);
														}
													}
												} catch(e) { /* ignore */ }

												select.on('change', function(){
													const selected = $(this).val() || [];
													applyVisibilityFromSelected(selected);
													try { localStorage.setItem(storeKey, JSON.stringify(selected)); } catch(e){}
													// Persist server-side
													fetch('{{ route('profile.save-widget-columns') }}', {
														method: 'POST',
														headers: {
															'Content-Type': 'application/json',
															'X-CSRF-TOKEN': '{{ csrf_token() }}'
														},
														body: JSON.stringify({ widget_id: {{ $widget['id'] }}, columns: selected })
													}).then(r => { /* optional: check resp */ }).catch(()=>{});
												});
											});
										</script>
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

			const lockBtn = document.getElementById("dashboardLockBtn");
    		const storageKey = "dashboardLock:{{ $dashboard_info['id'] }}";

			// Functions to enable/disable dragging and update button state
			function enableDragging() {
				$(".card").draggable("enable");
        		$(".card").resizable("enable");
        		$("#none_shall_pass").removeClass("dashboard-locked");
			}
			function disableDragging() {
				$(".card").draggable("disable");
				$(".card").resizable("disable");
				$("#none_shall_pass").addClass("dashboard-locked");
			}
			
			function initDragResize() {
				$(".card").draggable({
				addClasses: false,
				cursor: "grabbing", // Change the cursor to the move symbol
				stack: ".card", // What items to change z-index on so they don't interact
				containment: "#none_shall_pass", // Make sure you can't put the card outside of this div
				cancel: ".no-sort", // Make sure we don't attempt to move the card when we're actually in the map panning
				stop: function(event, ui) {
					if (isLocked())
						return;
					var positions = JSON.parse(localStorage.positions || "{}");
					positions[this.id] = ui.position; // Store by element ID
					localStorage.positions = JSON.stringify(positions);
				}
				}).resizable({
					stop: function(event, ui) {
						if (isLocked())
							return;
						var positions = JSON.parse(localStorage.positions || "{}");
						positions[this.id] = ui.position; // Store by element ID
						positions[this.id].width = ui.size.width;
						positions[this.id].height = ui.size.height;
						localStorage.positions = JSON.stringify(positions);
					}
				});
			}

			function isLocked() {
				return localStorage.getItem(storageKey) === "true";
			}

			function updateButtonUI() {
        		if (isLocked()) {
            		lockBtn.innerHTML = '<i class="fas fa-unlock"></i> Unlock Dashboard';
            		disableDragging();
        		}
				else {
            		lockBtn.innerHTML = '<i class="fas fa-lock"></i> Lock Dashboard';
            		enableDragging();
        		}
    		}
			
			// initialize drag/resize on page load
    			initDragResize();

			//Restore saved positions

			var sPositions = localStorage.positions || "{}";
			var positions = JSON.parse(sPositions);
			// If using localStorage
			$.each(positions, function(id, pos) {
				$("#" + id).css(pos);
			});

			// set intial lock state
			updateButtonUI();

			//	button click toggle handler
			lockBtn.addEventListener("click", function() {
				const newState = !isLocked();
				localStorage.setItem(storageKey, newState);
				updateButtonUI();
			});
		});
	</script>

	<script>
	// Update a Chart.js instance with new labels/datasets
	function applyChartData(chart, payload) {
		chart.data.labels = payload.labels || [];
		chart.data.datasets = (payload.datasets || []).map(ds => ({ ...ds }));
		chart.update();
	}

	// Call our Laravel endpoint to get a chart's filtered data
	async function fetchChartDataForWidget(widgetId, mapId, bbox) {
	var res = await fetch('{{ route('dashboard.update-bounds') }}', {
		method: 'POST',
		headers: {
		'Content-Type': 'application/json',
		'X-CSRF-TOKEN': '{{ csrf_token() }}'
		},
		body: JSON.stringify({
		widget_id: widgetId,
		map_id: mapId,
		bounds: {
			_northEast: { lat: bbox.north, lng: bbox.east },
			_southWest: { lat: bbox.south, lng: bbox.west }
		}
		})
	});
	if (!res.ok) throw new Error('Failed to fetch chart data ' + widgetId + ' ' + mapId);
	return res.json();
	}

	// If Chart.js isn't ready yet, don't try to update
	function chartJsReady() {
		return (typeof window.Chart !== 'undefined') && (typeof Chart.getChart === 'function');
	}

	// When any map broadcasts bbox, refresh only the charts linked to that map
	window.MapBus.addEventListener('map:bbox', async (e) => {
		if (!chartJsReady()) return;

		var { bbox } = e.detail;
		var mapID = e.detail.sourceWidgetId;

		// For each chart widget, find its canvas & Chart.js instance
		const widgets = document.querySelectorAll('.chart-widget[data-widget-id]');

		for (const el of widgets) {
			const widgetId = el.getAttribute('data-widget-id');
			const linkId   = el.getAttribute('data-map-link-id') || 'noLink321π';

			// Skip if not linked or linked to a different map
			if (linkId === 'noLink321π') continue;
			if (String(linkId) !== String(mapID)) continue;

			const canvas = el.querySelector('canvas');
			if (!canvas) continue;
			const chart = Chart.getChart(canvas);
			if (!chart) continue;
			try {
				const payload = await fetchChartDataForWidget(widgetId, mapID, bbox);
				if(payload.labels == 'dont') continue;
				applyChartData(chart, payload);
				if (payload.category_warning) {
    				alert("WARNING: This chart had too many categories, so only the first 100 are shown for readability.");
				}
			} catch (err) {
				console.error('Chart update failed for widget', widgetId, err);
			}
		}
	});
	</script>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8"></script>
	<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@next"></script>
	<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Page Specific JS File -->
@endpush



