@php
    $dashboard_name = $dashboard_info['name'];
@endphp
@extends('layouts.app')
@section('title', 'Add Widget')

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
                <div class="card-header">Add Widget to {{ $dashboard_name }}</div>
                <div class="card-body">
                    <form action="{{ route('profile.add-widgets', ['id' => $dashboard_info['id']]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="widget_name">Enter a widget name</label>
                            <input class="form-control mb-2" type="text" id="widget_name" name="widget_name" placeholder="Leave blank to use the geojson title"/>

                            <label for="widget_type">Select a widget type</label>
                            <select class="form-control mb-2" id="widget_type" name="widget_type">
                                @foreach ($widget_types as $widget_type)
                                    <option value="{{ $widget_type['id'] }}">{{ $widget_type['name'] }}</option>
                                @endforeach
                            </select>

                            <label for="map_filename">Select a geojson file</label>
                            <select class="form-control mb-2" id="map_filename" name="map_filename">
                                @foreach ($files as $file)
                                    <option value="{{ $file['filename'] }}">{{ $file['title'] }}</option>
                                @endforeach
                            </select>

                            {{-- Chart controls --}}
                            <div id="chart_forms" class="form-group" style="display:none;">
                                <label for="x_axis">Select the property you want to use on the x axis.</label>
                                <select class="form-control mb-2" id="x_axis" name="x_axis">
                                    <option value="">Loading..</option>
                                </select>

                                <label for="y_axis">Select the property you want to use on the y axis.</label>
                                <select class="form-control mb-2" id="y_axis" name="y_axis">
                                    <option value="">Loading..</option>
                                </select>
                            </div>

                            {{-- Table controls (multi-select w/ Select2 chips) --}}
                            <div id="table_column" class="form-group" style="display:none;">
                                <label class="mb-2" for="table_columns">Select the properties you want to appear on the table</label>
                                <div class="col-selector-wrap">
                                    <select id="table_columns" class="form-control mb-2" name="table_columns[]" multiple="multiple" style="width:100%;">
                                        <option value="">Loading..</option>
                                    </select>
                                </div>
                            </div>

                            <button class="btn btn-warning" type="submit">Submit</button>
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
    $(function () {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        function update_axis_select(filename) {
            $.post('/profile/get-file-metadata', { filename }).done(function (response) {
                $("#x_axis").empty();
                $("#y_axis").empty();
                // x axis options (all properties)
                $.each(response.x_axis || [], function(_, v){ $('#x_axis').append(`<option value="${v}">${v}</option>`); });
                // y axis options (COUNT + numeric props)
                $('#y_axis').append('<option value="COUNT">COUNT</option>');
                $.each(response.y_axis || [], function(_, v){ $('#y_axis').append(`<option value="${v}">SUM ${v}</option>`); });
            });
        }

        function update_table_select(filename) {
            $.post('/profile/get-file-metadata', { filename }).done(function (response) {
                const $sel = $('#table_columns');
                $sel.empty();
                $.each((response.x_axis || []), function(_, v) {
                    $sel.append(`<option value="${v}">${v}</option>`);
                });
                // refresh select2 choices (keeps chip UI)
                $sel.trigger('change.select2');
            });
        }

        // initialize Select2
        $('#table_columns').select2({
            placeholder: 'Select table properties',
            closeOnSelect: false,
            allowClear: true,
            dropdownParent: $('.col-selector-wrap')  //keep dropdown inside the styled box
        });

        $('#widget_type').on('change', function () {
            const t = $(this).val();
            if (t == 1) { // map
                $("#chart_forms").hide('slow');
                $("#table_column").hide('slow');
            } else if (t == 5) { // table
                $("#chart_forms").hide('slow');
                $("#table_column").show('slow');
                update_table_select($('#map_filename').val());
            } else { // charts
                $("#table_column").hide('slow');
                $("#chart_forms").show('slow');
                update_axis_select($('#map_filename').val());
            }
        });

        $('#map_filename').on('change', function () {
            const file = $(this).val();
            // refresh both sets; the visible block will show the right one
            update_axis_select(file);
            update_table_select(file);
        });

        $('#widget_type').trigger('change');
    });
    </script>
@endpush



