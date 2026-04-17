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
                </div>
                <div id = "graphAll">
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
        $("#graphAll").hide(0);
        widget_type = {{$widget_type}}
        if(widget_type == 1){
            // map options
            $("#mapAll").show(0);
            if($('#mapColors').val() != 3)
                $('#linkSelect').hide(0)
            if($('#mapColors').val() != 1)
                $('#geoWarning').hide()
        }
        else if(widget_type == 2){ // Line Graphs
            $("#lineGraph").show(0);
        }
        else if(widget_type > 2 && widget_type < 5){
            // graph options
            $("#graphAll").show(0);
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
@endpush