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
                                    <label for="mapColors">Map Color Options:</label>
                                    <select id="mapColors" name="mapColors">
                                        <?php
                                            try{
                                                if(array_key_exists('importColors', $metadata))
                                                    $color = $metadata['importColors'];
                                                else
                                                    $color = 0;
                                                // 0 for none, 1 for geojson, 2 for legend
                                                echo("<option value='0'".($color==0 ? " selected" : " ").">Default Map Color</option>");
                                                echo("<option value='1'".($color==1 ? " selected" : " ").">Import 'Color' fields from Geojson</option>");
                                                echo("<option value='2'".($color==2 ? " selected" : " ").">Custom Legend Colors</option>");
                                                echo("<option value='3'".($color==3 ? " selected" : " ").">Import from Graph Widget</option>");
                                            }
                                            catch(Exception $E){
                                                echo("Failed to load in map color options: ".E);
                                            }
                                        ?>
                                    </select><br>
                                    <label for="mapColors" id='geoWarning'> Importing from Geojson changes default color to purple.</label>
                                    <div id="linkSelect"><br>
                                        <label for="mapLink">Choose Map to Link to:</label>
                                        <select id="mapLink" name="mapLink">
                                            <?php
                                                $linkedWidget = $metadata['mapLinkID'] ?? -1;
                                                // sucker for inline php insertion, it's bad, I know...
                                                try{
                                                    foreach($graphWidgets as $widgetID => $widgetName){
                                                        echo("<option value='".$widgetID."'".(($widgetID == $linkedWidget) ? " selected" : "").">".$widgetName."</option>");
                                                    }
                                                }
                                                catch(Exception $E){
                                                    echo("Failed to load graph widget list".E);
                                                }
                                            ?>
                                        </select>
                                    </div>
                                    <br>
                                    <label for="legend_property">Select the property to use for legend labels</label>
                                    <select class="form-control mb-2" id="legend_property" name="legend_property">
                                        <option value="EnabledΣπ">-- auto detect --</option>
                                    </select>

                                    <div id="map_popup_group" class="form-group" style="display:none;">
                                        <label for="map_tooltip">Select which properties to show on popups</label>
                                        <select id="map_tooltip" name="map_tooltip[]" class="form-control mb-2" multiple style="width:100%;">
                                        </select>
                                        <label for="map_popup_event">Popup trigger</label>
                                        <select id="map_popup_event" name="popup_event" class="form-control mb-2">
                                            <option value="click">On click</option>
                                            <option value="hover">On hover</option>
                                            <option value="both">On click + hover</option>
                                        </select>

                                        <div id="popup_template_group" style="display:none;">
                                            <label for="popup_template">Or provide a custom popup template</label>
                                            <textarea id="map_popup" name="popup_template" class="form-control mb-2" rows="4" placeholder="Use html and placeholders like {property_name} to inject feature properties."></textarea>
                                        </div>
                                    </div>
                                    <br><button class="btn btn-warning" type="submit">Save and Exit</button>
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
                                <div>Graph Color Customization:</div>
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
                <div id = "nonLineGraph">
                    <div class="card-header">Edit Graph Widget</div>
                    <div class="card-body">
                        <div id="colorList">
                        <form action="{{ route('profile.edit-widgets', ['dash_id' => $dashboard_info['id'], 'id' => $widget['id']]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <div>Graph Color Customization:</div>
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
        $("#nonLineGraph").hide(0);
        widget_type = {{$widget_type}}
        if(widget_type == 1){
            // map options
            $("#mapAll").show(0);
            if($('#mapColors').val() != 3)
                $('#linkSelect').hide(0)
            else
                $('#linkSelect').show(0)
            if($('#mapColors').val() != 1)
                $('#geoWarning').hide()
            else
                $('#geoWarning').show()
        }
        else if(widget_type == 2){ // Line Graphs
            $("#lineGraph").show(0);
        }
        else if(widget_type > 2 && widget_type < 5){
            // graph options
            $("#nonLineGraph").show(0);
            // just had the for loop be in the html, nobody can tell me what to do!
        }
    });
    $('#mapColors').on('change', function(){
        if($(this).val() == 3)
            $('#linkSelect').show('slow');
        else
            $('#linkSelect').hide('slow');
        if($(this).val() == 1)
            $('#geoWarning').show('slow');
        else
            $('#geoWarning').hide('slow');
        
    });
</script>
<script src="https://unpkg.com/dompurify@2.4.0/dist/purify.min.js"></script>

<script>
    // Popup sanitization helpers (copied from add-widgets)
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function buildSafePopupHtml(template, properties) {
        const allowedTags = ['b','i','strong','em','br','p','ul','ol','li','a','span','div'];
        const allowedAttrs = ['href','title','target'];
        const cfg = {
            ALLOWED_TAGS: allowedTags,
            ALLOWED_ATTR: allowedAttrs,
            FORBID_ATTR: ['style', 'onclick', 'onerror', 'onload']
        };

        const safeTemplate = DOMPurify.sanitize(template || '', cfg);

        const substituted = safeTemplate.replace(/\{([^}]+)\}/g, function(_, key) {
            const val = properties && properties[key] != null ? properties[key] : '';
            return escapeHtml(String(val));
        });

        return DOMPurify.sanitize(substituted, cfg);
    }

    // Populate legend choices from the geojson metadata
    function update_legend_select(filename) {
        $.post('/profile/get-file-metadata', { filename }).done(function (response) {
            try {
                const $sel = $('#legend_property');
                const current = $sel.val() || '';

                $sel.empty();
                $sel.append($('<option>').val('EnabledΣπ').text('Disabled'));

                const cols = Array.isArray(response && response.table_columns) ? response.table_columns : [];
                $.each(cols, function(_, value) { $sel.append($('<option>').val(value).text(value)); });

                if (current && $sel.find(`option[value="${current}"]`).length) {
                    $sel.val(current);
                } else if (!current) {
                    $sel.val('EnabledΣπ');
                }
                $sel.trigger('change');
            } catch (err) {
                console.error('update_legend_select error:', err);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error('Failed to fetch file metadata for legend:', textStatus, errorThrown);
        });
    }

    // Populate popup options and toggle custom template UI
    function update_popup_select(filename) {
        $.post('/profile/get-file-metadata', { filename }).done(function (response) {
            const $sel = $('#map_tooltip');
            const current = $sel.val() || [];

            $sel.empty();
            $sel.append('<option value="ALL_PROPERTIES">All properties</option>');

            $.each(response.table_columns || [], function(_, value) {
                $sel.append(`<option value="${value}">${value}</option>`);
            });

            $sel.append('<option value="custom">Custom popup...</option>');

            if (Array.isArray(current) && current.length) {
                $sel.val(current);
            } else if (current) {
                $sel.val([current]);
            } else {
                $sel.val([]);
            }
            $sel.trigger('change');
        });
    }

    $(function () {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });
        // initialize popup selector
        $('#map_tooltip').select2({
            placeholder: 'Select popup properties',
            closeOnSelect: false,
            allowClear: true,
            width: '100%'
        });

        // read current values from server-side metadata
        const mapFilename = @json($metadata['map_filename'] ?? '');
        const initialLegend = @json($metadata['legend']['property'] ?? $metadata['legend_property'] ?? '');
        const initialTooltip = @json($metadata['map_tooltip'] ?? []);
        const initialPopupTemplate = @json($metadata['popup_template'] ?? '');
        const initialPopupEvent = @json($metadata['popup_event'] ?? 'click');

        console.log('edit-widgets init', { mapFilename, initialLegend, initialTooltip, initialPopupTemplate, initialPopupEvent });
        // set initial values (so update_* functions can preserve selections)
        if (initialLegend) $('#legend_property').val(initialLegend);
        if (Array.isArray(initialTooltip) && initialTooltip.length) $('#map_tooltip').val(initialTooltip);
        if (initialPopupTemplate) $('#map_popup').val(initialPopupTemplate);
        if (initialPopupEvent) $('#map_popup_event').val(initialPopupEvent);

        if (mapFilename) {
            const filenameBase = mapFilename.replace(/^.*[\\/]/, '');
            update_legend_select(filenameBase);
            update_popup_select(filenameBase);
            $('#map_popup_group').show(0);
        }

        // show/hide custom template area when 'custom' selected or template present
        $('#map_tooltip').on('change', function () {
            const val = $(this).val() || [];
            if (Array.isArray(val) ? val.indexOf('custom') !== -1 : val === 'custom') {
                $('#popup_template_group').show('slow');
            } else {
                $('#popup_template_group').hide('slow');
            }
        });

        // If a template already exists, ensure the template area is visible
        if (initialPopupTemplate && initialPopupTemplate.length) {
            $('#popup_template_group').show(0);
        }
    });
</script>
@endpush