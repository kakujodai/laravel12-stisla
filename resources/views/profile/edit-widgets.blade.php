@php
    $dashboard_name = $dashboard_info['name'];
@endphp
@extends('layouts.app')
@section('title', 'Edit widget')

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* pill chips + bordered wrapper look */
        .col-selector-wrap .select2-container--default .select2-selection--multiple {
            border: 1px solid #000;
            border-radius: 6px;
            padding: 6px 6px 2px 6px;
            min-height: 44px;
            box-shadow: none;
        }
        .col-selector-wrap .select2-container--default .select2-selection__rendered {
            display: flex; flex-wrap: wrap; gap: 3px; align-items: center;
        }
        .col-selector-wrap .select2-container--default .select2-selection__choice {
            background:#ececec; border:1px solid #c9c9c9; color:#222;
            padding:4px 8px; border-radius:4px; margin:0; font-weight:600; letter-spacing:.2px;
        }
        .col-selector-wrap .select2-dropdown {
            border-top:1px solid #e2e2e2; box-shadow:none; border-radius:0 0 6px 6px;
        }
    </style>
@endpush

@section('content')
<div class="content">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div id="mapAll">
                    <div class="card-header">Edit Map Widget</div>
                    <div class="card-body">
                        <div id="mapOptions" class="form-group">
                            <form action="{{ route('profile.edit-widgets', ['dash_id' => $dashboard_info['id'], 'id' => $widget['id']]) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="importColors">Map reads 'color' property in geojson. Changes the default color from a blue to a purple.</label>
                                    <input type="checkbox" id="importColors" name="importColors" value="importColors">
                                    <label for="importColors">Import geojson colors</label><br>
                                    Enabling can affect load times, depending on the size of the geojson.<br><br>
                                    <button class="btn btn-warning" type="submit">Save and Exit</button>
                                </div>
                            </form>
                            <form action="{{ route('profile.edit-widgets', ['dash_id' => $dashboard_info['id'], 'id' => $widget['id']]) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="legend_property">Legend Property</label>
                                        <select class="form-control mb-2" id="legend_property" name="legend_property">
                                            <option value="">Loading...</option>
                                        </select>

                                    <div id="map_popup_group" class="form-group">
                                            <label for="map_tooltip">Select which properties to show on popups</label>
                                                <select id="map_tooltip" name="map_tooltip[]" class="form-control mb-2" multiple style="width:100%;">
                                                </select>

                                            <label for="popup_event">Popup trigger</label>
                                                <select id="popup_event" name="popup_event" class="form-control mb-2">
                                                    <option value="click">On click</option>
                                                    <option value="hover">On hover</option>
                                                    <option value="both">On click + hover</option>
                                                </select>

                                        <div id="popup_template_group" style="display:none;">
                                            <label for="popup_template">Custom popup template</label>
                                            <textarea
                                                id="popup_template" name="popup_template" class="form-control mb-2" rows="4">{{ $metadata['popup_template'] ?? '' }}</textarea>
                                        </div>
                                    </div>

                                    <button class="btn btn-warning" type="submit">Save and Exit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="lineGraph">
                    <div class="card-header">Edit Graph Widget</div>
                        <div class="card-body">
                        <div id="colorList">
                        <form action="{{ route('profile.edit-widgets', ['dash_id' => $dashboard_info['id'], 'id' => $widget['id']]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <?php
                                    try{
                                        echo '<input type="color" id="lineColor" name="lineColor" value="'.$metadata['graphSettings']['lineColor'].'"> ';
                                        echo '<label for="lineColor"> Line Color</label><br>';
                                        echo '<input type="color" id="lineShade" name="lineShade" value="'.$metadata['graphSettings']['shadeColor'].'"> ';
                                        echo '<label for="lineShade">Shading Color</label><br>';
                                    }
                                    catch(Exception $E){
                                        echo("failed to load in the line graph options".$E);
                                    }
                                ?>
                            </div>
                        <button class="btn btn-warning" type="submit">Save and Exit</button>
                        </form>
                        </div>
                        </div>
                    </div>
                </div>
                <div id = "graphAll">
                    <div class="card-header">Edit Graph Widget</div>
                        <div class="card-body">
                        <div id="colorList">
                        <form action="{{ route('profile.edit-widgets', ['dash_id' => $dashboard_info['id'], 'id' => $widget['id']]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <?php
                                    try { # fails if we're loading a map lol
                                        if(array_key_exists('colorMap', $metadata))
                                            foreach ($metadata['colorMap'] as $key => $value) {
                                                echo '<input type="color" id="color'.$key.'" name="color'.$key.'" value="'.$value.'"> ';
                                                echo '<label for="color'.$key.'">  '.$key.'</label><br>';
                                            }
                                        else echo "color map not detected in metadata";
                                    }
                                    catch(Exception $E){
                                        echo "Color Map fetch failed".$E;
                                    }
                                ?>
                            </div>
                            <button class="btn btn-warning" type="submit">Save and Exit</button>
                        </form>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{--Select2 JS--}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready( function () {
        // replace the 'slow' with 0 once done
        $("#mapAll").hide(0);
        $("#lineGraph").hide(0);
        $("#graphAll").hide(0);
        widget_type = {{$widget_type}};
        // Ensure AJAX posts include Laravel CSRF token
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });
        if(widget_type == 1){
            // map options
            $("#mapAll").show(0);
        }
        else if(widget_type == 2){ // Line Graphs
            $("#lineGraph").show(0);
        }
        else if(widget_type > 2 && widget_type < 5){
            // graph options
            $("#graphAll").show(0);
            // just had the for loop be in the html, nobody can tell me what to do!
        }
        //Update legend stuff
        function update_legend_select(filename) {
            $.post('/profile/get-file-metadata', { filename }).done(function (response) {
                const $sel = $('#legend_property');
                const current = @json($metadata['legend_property'] ?? '');
                $sel.empty();
                $.each(response.table_columns || [], function(_, value) {
                    $sel.append(`<option value="${value}">${value}</option>`);
                });
            $sel.val(current);
            });
        }
        //update popup stuff
        function update_popup_select(filename) {
            $.post('/profile/get-file-metadata', { filename }).done(function (response) {
                const $sel = $('#map_tooltip');
                const current = @json($metadata['map_tooltip'] ?? []);
                $sel.empty();
                $sel.append('<option value="ALL_PROPERTIES">All properties</option>');
                $.each(response.table_columns || [], function(_, value) {
                    $sel.append(`<option value="${value}">${value}</option>`);
                });
                $sel.append('<option value="custom">Custom popup...</option>');
                $sel.val(Array.isArray(current) ? current : [current]).trigger('change');
            });
        }
        //initialize select2 for multiple select elements before populating
        $('#map_tooltip').select2({
            placeholder: 'Select popup properties',
            closeOnSelect: false,
            allowClear: true
        });
        //popup custom show/hide
        $('#map_tooltip').on('change', function () {
            const values = $(this).val() || [];
            if (values.includes('custom')) {
                $('#popup_template_group').show('slow');
            }
            else {
                $('#popup_template_group').hide('slow');
            }
        });
        //load map widget's geojson properties
        if (widget_type == 1) {
            const filename = @json($metadata['map_filename'] ?? $metadata['filename'] ?? null);
            if (filename) {
                update_legend_select(filename);
                update_popup_select(filename);
            }
            $('#popup_event').val(@json($metadata['popup_event'] ?? 'click'));
        }
    });
</script>
@endpush