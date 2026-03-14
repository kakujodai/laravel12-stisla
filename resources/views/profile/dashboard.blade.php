@extends('layouts.app')

@section('title', $dashboard_info['name'])

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" integrity="sha512-ELV+xyi8IhEApPS/pSj66+Jiw+sOT1Mqkzlh8ExXihe4zfqbWkxPRi8wptXIO9g73FSlhmquFlUOuMSoXz5IRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/library/Leaflet.Legend-master/src/leaflet.legend.css">

    <style>
        .dashboard-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .dashboard-toolbar-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        #dashboard-canvas {
            position: relative;
            min-height: 1400px;
            width: 100%;
            border: 1px solid #dfe3e7;
            border-radius: 12px;
            background: #f8f9fa;
            overflow: auto;
            padding: 12px;
        }

        .dashboard-widget {
            position: absolute;
            z-index: 1;
        }

        .dashboard-widget .card {
            height: 100%;
            width: 100%;
            margin-bottom: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
        }

        .dashboard-widget .card-body {
            overflow: hidden;
        }

        .dashboard-widget.table-widget .card-body {
            overflow: auto;
        }

        .dashboard-locked .ui-resizable-handle {
            display: none !important;
        }

        .col-selector-wrap .select2-container--default .select2-selection--multiple {
            border: none;
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
            background: #ececec;
            border: 1px solid #c9c9c9;
            color: #222;
            padding: 4px 8px;
            border-radius: 4px;
            margin: 0;
            font-weight: 600;
            letter-spacing: .2px;
        }

        .col-selector-wrap .select2-dropdown {
            border-top: 1px solid #e2e2e2;
            box-shadow: none;
            border-radius: 0 0 6px 6px;
        }

        .col-selector-wrap .select2-container .select2-dropdown--below {
            width: 100% !important;
        }

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

        /* Leaflet legend styles */
        .leaflet-control.legend {
            background: #fff;
            padding: 6px 8px;
            font: 12px/1.4 "Helvetica Neue", Arial, sans-serif;
            box-shadow: 0 1px 3px rgba(0,0,0,0.35);
            border-radius: 4px;
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-toolbar">
        <h3 class="mb-0">{{ $dashboard_info['name'] }}</h3>

        <div class="dashboard-toolbar-actions">
            <a href="{{ route('profile.add-widgets', ['id' => $dashboard_info['id']]) }}" class="btn btn-primary">
                Add Widget
            </a>
            <button type="button" class="btn btn-secondary" id="dashboardLockBtn">
                <i class="fas fa-lock"></i> Lock Dashboard
            </button>
        </div>
    </div>

    <div id="dashboard-canvas">
        <!-- Our Dependencies for just leaflet maps -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-ajax/2.1.0/leaflet.ajax.min.js" integrity="sha512-Abr21JO2YqcJ03XGZRPuZSWKBhJpUAR6+2wH5zBeO4wAw4oksr8PRdF+BKIRsxvCdq+Mv4670rZ+dLnIyabbGw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://makinacorpus.github.io/Leaflet.Spin/docs/spin/dist/spin.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Leaflet.Spin/1.1.2/leaflet.spin.js" integrity="sha512-em8lnJhVwVTWfz2Qg/1hRvLz6gTM4RiXs5gTywZMz/NNunZUybf1PsYHKAjcdx2/+zdRwU4PzOM9CwC5o2ko0g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js" integrity="sha512-TiMWaqipFi2Vqt4ugRzsF8oRoGFlFFuqIi30FFxEPNw58Ov9mOy6LgC05ysfkxwLE0xVeZtmr92wVg9siAFRWA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

        <script>
            async function getJsonFromServer(url) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const jsonData = await response.json();
                    return jsonData;
                } catch (error) {
                    console.error("Error fetching JSON:", error);
                    return null;
                }
            }
        </script>

        <script>
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

            function debounce(fn, ms) {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), ms);
                };
            }
        </script>

        @foreach ($widgets as $index => $widget)
            @php
                $layout = $widget['layout'] ?? [];
                $defaultTop = 20 + ($index * 30);
                $defaultLeft = 20 + (($index % 2) * 540);

                if ($widget['widget_type_id'] == 1) {
                    $defaultWidth = 500;
                    $defaultHeight = 420;
                } elseif ($widget['widget_type_id'] > 1 && $widget['widget_type_id'] <= 4) {
                    $defaultWidth = $widget['widget_type_id'] == 4 ? 360 : 520;
                    $defaultHeight = 320;
                } else {
                    $defaultWidth = 1000;
                    $defaultHeight = 500;
                }

                $top = $layout['top'] ?? $defaultTop;
                $left = $layout['left'] ?? $defaultLeft;
                $width = $layout['width'] ?? $defaultWidth;
                $height = $layout['height'] ?? $defaultHeight;
            @endphp

            @if ($widget['widget_type_id'] == 1)
                <div
                    id="widget-{{ $widget['id'] }}"
                    class="dashboard-widget map-widget"
                    data-widget-id="{{ $widget['id'] }}"
                    style="top: {{ $top }}px; left: {{ $left }}px; width: {{ $width }}px; height: {{ $height }}px;"
                >
                    <div class="card">
                        <x-widget-header
                            :name="$widget['name']"
                            :widget-id="$widget['id']"
                            :dashboard-id="$dashboard_info['id']"
                            :random-id="$widget['random_id']"
                            :widget-type-id="$widget['widget_type_id']"
                            :has-settings="true"
                        />

                        <div class="card-body" style="height: calc(100% - 56px);">
                            <div class="no-sort" id="{{ $widget['random_id'] }}" style="height:100%;"></div>

                            <script>
                                const overlayMaps{{ $widget['random_id'] }} = {};

                                @foreach ($all_geojsons as $each_geojson)
                                    var {{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME) }}{{ $widget['random_id'] }} = L.geoJson.ajax();
                                    overlayMaps{{ $widget['random_id'] }}.{{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME) }} = {{ pathinfo($each_geojson['filename'], PATHINFO_FILENAME) }}{{ $widget['random_id'] }};
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

                                var osmwmsLayer = L.tileLayer.wms('http://ows.mundialis.de/services/service?', { layers: 'OSM-WMS' });
                                var topowmsLayer = L.tileLayer.wms('http://ows.mundialis.de/services/service?', { layers: 'TOPO-WMS' });

                                var baseMaps = {
                                    "OpenStreet": osm,
                                    "OpenStreet Topographic": osmT,
                                    "OpenStreet Humanitarian": osmH,
                                    "Open Street Map OSM-WMS": osmwmsLayer,
                                    "Open Street Map TOPO-WMS": topowmsLayer,
                                };

                                function createCircleMarker(feature, latlng) {
                                    var thecolor = "{{$widget['importColor']}}" ? (feature.properties.color || '#00AA00') : '#3388ff';
                                    return L.circleMarker(latlng, {
                                        radius: 3,
                                        color: thecolor,
                                        weight: 1,
                                        fillOpacity: 0.8
                                    });
                                }

                                var {{ pathinfo($widget['filename'], PATHINFO_FILENAME) }}{{ $widget['random_id'] }} =
                                    new L.GeoJSON.AJAX("{{ route('profile.get-geojson', ['filename' => pathinfo($widget['filename'], PATHINFO_FILENAME)]) }}", {
                                        pointToLayer: createCircleMarker,
                                        style: function (feature) {
                                            var theColor = "{{$widget['importColor']}}" ? (feature.properties.color || '#663399') : '#3388ff';
                                            return {
                                                color: theColor,
                                                fillColor: theColor,
                                                weight: 1,
                                                fillOpacity: 0.8,
                                                opacity: 1,
                                            };
                                        },
                                        onEachFeature: function (feature, layer) {
                                            layer.bindPopup('<pre>' + JSON.stringify(feature.properties, null, ' ').replace(/[\{\}"]/g, '') + '</pre>');
                                        },
                                    });

                                overlayMaps{{ $widget['random_id'] }}.{{ pathinfo($widget['filename'], PATHINFO_FILENAME) }} = {{ pathinfo($widget['filename'], PATHINFO_FILENAME) }}{{ $widget['random_id'] }};

                                var map{{ $widget['random_id'] }} = L.map('{{ $widget['random_id'] }}', {
                                    center: [0,0],
                                    zoom: 14,
                                    layers: [osm, {{ str_replace('-', '', pathinfo($widget['filename'], PATHINFO_FILENAME)) }}{{ $widget['random_id'] }}]
                                });

                                // Legend integration using Leaflet.Legend plugin
                                var legendControl{{ $widget['random_id'] }} = null;

                                function buildLegends{{ $widget['random_id'] }}() {
                                    return Object.keys(overlayMaps{{ $widget['random_id'] }}).map(function (name) {
                                        var layer = overlayMaps{{ $widget['random_id'] }}[name];
                                        var color = '#3388ff';
                                        try {
                                            if (layer && layer.options && layer.options.color) color = layer.options.color;
                                            var first = (layer && layer.getLayers && layer.getLayers()[0]) || null;
                                            if (first && first.feature && first.feature.properties && first.feature.properties.color) {
                                                color = first.feature.properties.color;
                                            }
                                        } catch (err) { }

                                        var type = 'rectangle';
                                        try {
                                            var firstLayer = (layer && layer.getLayers && layer.getLayers()[0]) || null;
                                            if (firstLayer && firstLayer.feature && firstLayer.feature.geometry) {
                                                var g = firstLayer.feature.geometry.type;
                                                if (g === 'Point' || g === 'MultiPoint') type = 'circle';
                                                else if (g === 'LineString' || g === 'MultiLineString') type = 'polyline';
                                                else if (g === 'Polygon' || g === 'MultiPolygon') type = 'polygon';
                                            }
                                        } catch(e) {}

                                        return {
                                            label: name,
                                            type: type,
                                            color: color,
                                            layers: layer
                                        };
                                    });
                                }

                                function updateLegend{{ $widget['random_id'] }}() {
                                    try {
                                        if (legendControl{{ $widget['random_id'] }}) {
                                            map{{ $widget['random_id'] }}.removeControl(legendControl{{ $widget['random_id'] }});
                                        }
                                    } catch (e) { }

                                    legendControl{{ $widget['random_id'] }} = L.control.legend({
                                        position: 'bottomright',
                                        title: 'Legend',
                                        legends: buildLegends{{ $widget['random_id'] }}(),
                                        collapsed: false,
                                        symbolWidth: 18,
                                        symbolHeight: 18
                                    }).addTo(map{{ $widget['random_id'] }});
                                }

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

                                var debouncedBroadcast{{ $widget['random_id'] }} = debounce(broadcastBBox, 200);
                                let mapDataLoaded{{ $widget['random_id'] }} = false;

                                map{{ $widget['random_id'] }}.on('moveend zoomend', () => {
                                    if (!mapDataLoaded{{ $widget['random_id'] }}) return;
                                    saveMapView{{ $widget['random_id'] }}();
                                    debouncedBroadcast{{ $widget['random_id'] }}();
                                });

                                {{ pathinfo($widget['filename'], PATHINFO_FILENAME) }}{{ $widget['random_id'] }}.on('data:loaded', function () {
                                    var bounds = {{ pathinfo($widget['filename'], PATHINFO_FILENAME) }}{{ $widget['random_id'] }}.getBounds();

                                    const v = restoreMapView{{ $widget['random_id'] }}();
                                    if (v && Number.isFinite(v.lat) && Number.isFinite(v.lng) && Number.isFinite(v.zoom)) {
                                        map{{ $widget['random_id'] }}.setView([v.lat, v.lng], v.zoom);
                                    } else {
                                        map{{ $widget['random_id'] }}.fitBounds(bounds);
                                        saveMapView{{ $widget['random_id'] }}();
                                    }

                                    {{ pathinfo($widget['filename'], PATHINFO_FILENAME) }}{{ $widget['random_id'] }}.eachLayer(function (layer) {
                                        layer.bindPopup('<pre>'+JSON.stringify(layer.feature.properties,null,' ').replace(/[\{\}"]/g,'')+'</pre>');
                                    });

                                    broadcastBBox();
                                    updateLegend{{ $widget['random_id'] }}();
                                    mapDataLoaded{{ $widget['random_id'] }} = true;
                                });

                                const resizeObserver{{ $widget['random_id'] }} = new ResizeObserver(() => {
                                    map{{ $widget['random_id'] }}.invalidateSize();
                                });
                                resizeObserver{{ $widget['random_id'] }}.observe(document.getElementById("{{ $widget['random_id'] }}"));

                                //Add layer Control to map
                                var layerControl = L.control.layers(baseMaps, overlayMaps{{ $widget['random_id'] }}).addTo(map{{ $widget['random_id'] }});

                                // Legend is provided by Leaflet.Legend plugin and updated via updateLegend{{ $widget['random_id'] }}()


                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                                    id: 'mapbox/streets-v11',
                                }).addTo(map{{ $widget['random_id'] }});

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
                                                updateLegend{{ $widget['random_id'] }}();
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
                    <div
                        id="widget-{{ $widget['id'] }}"
                        class="dashboard-widget chart-widget-wrap"
                        data-widget-id="{{ $widget['id'] }}"
                        style="top: {{ $top }}px; left: {{ $left }}px; width: {{ $width }}px; height: {{ $height }}px;"
                    >
                        <div class="card">
                            <x-widget-header 
   									:name="$widget['name']" 
						    		:widget-id="$widget['id']" 
						    		:dashboard-id="$dashboard_info['id']" 
									:random-id="$widget['random_id']"
									:widget-type-id="$widget['widget_type_id']" 
									:has-settings="true"
								/>
							<div id="sortable-cards{{ $widget['id'] }}" class="card">
								<div class="card-body" style="height: calc(100% - 56px);">
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

            @else
                <div
                    id="widget-{{ $widget['id'] }}"
                    class="dashboard-widget table-widget"
                    data-widget-id="{{ $widget['id'] }}"
                    style="top: {{ $top }}px; left: {{ $left }}px; width: {{ $width }}px; height: {{ $height }}px;"
                >
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-10 text-left">
                                    <h2 class="card-title">{{ $widget['name'] }}</h2>
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

                        <div class="card-body" style="height: calc(100% - 130px);">
                            <div class="table-responsive">
                                <table id="table-{{ $widget['random_id'] }}" class="table table-striped dashboard-table" style="z-index: 10;">
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
                                        const table = tableEl.DataTable();
                                        const select = $("#colSelect-{{ $widget['random_id'] }}");
                                        const headers = tableEl.find('thead th').map(function(){ return $(this).text().trim(); }).get();

                                        if (typeof select.select2 === 'function') {
                                            select.select2({
                                                placeholder: 'Columns',
                                                width: 'resolve',
                                                closeOnSelect: false,
                                                dropdownParent: $('#colWrap-{{ $widget['random_id'] }}')
                                            });
                                        }

                                        const storeKey = 'tableCols-{{ $widget['random_id'] }}';

                                        function applyVisibilityFromSelected(selected) {
                                            const s = new Set((selected || []).map(String));
                                            headers.forEach(function(header, idx) {
                                                const visible = s.has(header);
                                                table.column(idx).visible(visible, false);
                                            });
                                            table.draw(false);
                                        }

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
                                        } catch(e) {}

                                        select.on('change', function() {
                                            const selected = $(this).val() || [];
                                            applyVisibilityFromSelected(selected);
                                            try { localStorage.setItem(storeKey, JSON.stringify(selected)); } catch(e) {}

                                            fetch('{{ route('profile.save-widget-columns') }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                },
                                                body: JSON.stringify({ widget_id: {{ $widget['id'] }}, columns: selected })
                                            }).then(r => {}).catch(() => {});
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

    <script>
		document.addEventListener("DOMContentLoaded", function () {
			const lockBtn = document.getElementById("dashboardLockBtn");
			const storageKey = "dashboardLock:{{ $dashboard_info['id'] }}";
			const canvas = $("#dashboard-canvas");
			const widgets = $(".dashboard-widget");

			function isLocked() {
				return localStorage.getItem(storageKey) === "true";
			}

			function saveWidgetLayout($widget, position, size = null) {
				$.ajax({
					url: "{{ route('profile.dashboard.save-widget-layout') }}",
					method: "POST",
					data: {
						_token: "{{ csrf_token() }}",
						widget_id: $widget.data("widget-id"),
						top: Math.round(position.top),
						left: Math.round(position.left),
						width: size ? Math.round(size.width) : Math.round($widget.outerWidth()),
						height: size ? Math.round(size.height) : Math.round($widget.outerHeight())
					},
					success: function (response) {
						console.log("layout saved", {
							widget_id: $widget.data("widget-id"),
							response: response
						});
					},
					error: function (xhr) {
						console.error("layout save failed", xhr.status, xhr.responseText);
					}
				});
			}

			function enableDragging() {
				widgets.draggable("enable");
				widgets.resizable("enable");
				canvas.removeClass("dashboard-locked");
			}

			function disableDragging() {
				widgets.draggable("disable");
				widgets.resizable("disable");
				canvas.addClass("dashboard-locked");
			}

			function updateButtonUI() {
				if (!lockBtn) return;

				if (isLocked()) {
					lockBtn.innerHTML = '<i class="fas fa-unlock"></i> Unlock Dashboard';
					disableDragging();
				} else {
					lockBtn.innerHTML = '<i class="fas fa-lock"></i> Lock Dashboard';
					enableDragging();
				}
			}

			if (widgets.length) {
				widgets.draggable({
					addClasses: false,
					cursor: "grabbing",
					stack: ".dashboard-widget",
					containment: "#dashboard-canvas",
					cancel: ".no-sort, .leaflet-container, .chart-widget canvas, table, select, option, input, button, a, .dataTables_wrapper",
					stop: function (event, ui) {
						if (isLocked()) return;
						saveWidgetLayout($(this), ui.position);
					}
				}).resizable({
					minWidth: 300,
					minHeight: 220,
					handles: "n, e, s, w, ne, se, sw, nw",
					stop: function (event, ui) {
						if (isLocked()) return;
						saveWidgetLayout($(this), ui.position, ui.size);
					}
				});
			}

			updateButtonUI();

			if (lockBtn) {
				lockBtn.addEventListener("click", function () {
					const newState = !isLocked();
					localStorage.setItem(storageKey, String(newState));
					updateButtonUI();
				});
			} else {
				console.warn("dashboardLockBtn not found");
			}
		});
	</script>

	<script>
		function applyChartData(chart, payload) {
			chart.data.labels = payload.labels || [];
			chart.data.datasets = (payload.datasets || []).map(ds => ({ ...ds }));
			chart.update();
		}

		async function fetchChartDataForWidget(widgetId, mapId, bbox) {
			const res = await fetch('{{ route('dashboard.update-bounds') }}', {
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

			if (!res.ok) {
				throw new Error('Failed to fetch chart data ' + widgetId + ' ' + mapId);
			}

			return res.json();
		}

		function chartJsReady() {
			return (typeof window.Chart !== 'undefined') && (typeof Chart.getChart === 'function');
		}

		window.MapBus.addEventListener('map:bbox', async (e) => {
			if (!chartJsReady()) return;

			const { bbox } = e.detail;
			const mapID = e.detail.sourceWidgetId;

			const widgets = document.querySelectorAll('.chart-widget[data-widget-id]');

			for (const el of widgets) {
				const widgetId = el.getAttribute('data-widget-id');
				const linkId = el.getAttribute('data-map-link-id') || 'noLink321π';

				if (linkId === 'noLink321π') continue;
				if (String(linkId) !== String(mapID)) continue;

				const canvas = el.querySelector('canvas');
				if (!canvas) continue;

				const chart = Chart.getChart(canvas);
				if (!chart) continue;

				try {
					const payload = await fetchChartDataForWidget(widgetId, mapID, bbox);
					if (payload.labels === 'dont') continue;

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
	<script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script>
	<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8"></script>
	<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@next"></script>
	<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="/library/Leaflet.Legend-master/src/leaflet.legend.js"></script>
@endpush