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
                        </div>
                    </form>
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
        $("#graphAll").hide(0);
        widget_type = {{$widget_type}}
        if(widget_type == 1){
            // map options
            $("#mapAll").show(0);
        }
        else if(widget_type > 1 && widget_type < 5){
            // graph options
            $("#graphAll").show(0);
            // just had the for loop be in the html, nobody can tell me what to do!
        }
    });
</script>
@endpush