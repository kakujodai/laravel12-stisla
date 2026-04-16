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
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
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

        .col-selector-wrap .select2-container--default .select2-selection--multiple .select2-selection__choice {
            position: relative;
            padding-left: 26px;
        }

        .col-selector-wrap .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            border: 0;
            border-right: 0;
            background: transparent;
            position: absolute;
            left: 6px;
            top: 50%;
            transform: translateY(-50%);
            line-height: 1;
        }

        .col-selector-wrap .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            padding-left: 0;
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
                            <input
                                class="form-control mb-2"
                                type="text"
                                id="widget_name"
                                name="widget_name"
                                placeholder="Leave blank to use the geojson title"
                            />

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

                            <div id="map_forms" class="form-group" style="display:none;">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="importColors" name="importColors" value="importColors">
                                    <label class="form-check-label" for="importColors">Use GeoJSON color property for map colors</label>
                                </div>

                                <label for="legend_property">Select the property to use for legend labels</label>
                                <select class="form-control mb-2" id="legend_property" name="legend_property">
                                    <option value="">-- auto detect --</option>
                                </select>

                                <div id="map_popup_group" class="form-group" style="display:none;">
                                    <label for="map_tooltip">Select which properties to show on popups</label>
                                    <select id="map_tooltip" name="map_tooltip[]" class="form-control mb-2" multiple style="width:100%;">
                                        <option value="">-- auto detect --</option>
                                    </select>
                                    <label for="map_popup_event">Popup trigger</label>
                                    <select id="map_popup_event" name="popup_event" class="form-control mb-2">
                                        <option value="click">On click</option>
                                        <option value="hover">On hover</option>
                                        <option value="both">On click + hover</option>
                                    </select>

                                    <div id="popup_template_group" style="display:none;">
                                        <label for="popup_template">Or provide a custom popup template</label>
                                        <textarea id="map_popup" name="popup_template" class="form-control mb-2" rows="4" placeholder="Use html and placeholders like {property_name} to inject feature properties. Example: '<strong>Name:</strong> {name}<br>Population: {pop}'"></textarea>

                                    </div>
                                </div>
                            </div>

                            {{-- Chart controls --}}
                            <div id="chart_forms" class="form-group" style="display:none;">
                                <label for="norm_control">Select whether or not to normalize multiple values.</label>
                                <select class="form-control mb-2" id="norm_control" name="norm_control">
                                    <option value="NOPE">Do not normalize</option>
                                    <option value="YEAH">Normalize multiple values</option>
                                </select>

                                <div id="x_axis_group" class="form-group">
                                    <label for="x_axis">Select the property you want to use on the x axis.</label>
                                    <select class="form-control mb-2" id="x_axis" name="x_axis">
                                        <option value="">Loading...</option>
                                    </select>
                                </div>

                                <div id="y_axis_group" class="form-group">
                                    <label for="y_axis">Select the property you want to use on the y axis.</label>
                                    <select class="form-control mb-2" id="y_axis" name="y_axis">
                                        <option value="">Loading...</option>
                                    </select>
                                </div>

                                <label for="mapLinkID" class="d-inline-flex align-items-center gap-1">
                                    <span>Decide which map widget to link to</span>
                                    <i
                                        class="fas fa-info-circle text-muted ms-1"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="right"
                                        title="Choose which existing map widget this chart should follow. When that map moves or zooms, the chart updates using only the visible map area."
                                        style="cursor: pointer;"
                                    ></i>
                                </label>
                                <select class="form-control mb-2" id="mapLinkID" name="mapLinkID">
                                    <option value="noLink321π">No Linking</option>
                                    @foreach ($mapWidgets as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>

                                <div id="norm_form" class="form-group" style="display:none;">
                                    <label for="norm_calc">Select which operation to join with.</label>
                                    <select class="form-control mb-2" id="norm_calc" name="norm_calc">
                                        <option value="average">Average</option>
                                        <option value="count">Count</option>
                                        <option value="max">Max</option>
                                        <option value="min">Min</option>
                                        <option value="summation">Summation</option>
                                    </select>

                                    <div id="norm_form1" class="form-group" style="display:none;">
                                        <label for="vars">Loading...</label><br>
                                    </div>
                                </div>
                            </div>

                            {{-- Table controls --}}
                            <div id="table_column" class="form-group" style="display:none;">
                                <label class="mb-2" for="table_columns">Select the properties you want to appear on the table</label>

                                <div class="col-selector-wrap">
                                    <select id="table_columns" class="form-control mb-2" name="table_columns[]" multiple="multiple" style="width:100%;">
                                        <option value="">Loading...</option>
                                    </select>
                                </div>

                                <div class="form-check mt-3 mb-2">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="enableTableMapLink"
                                        name="enableTableMapLink"
                                        value="1"
                                    >
                                    <label class="form-check-label d-inline-flex align-items-center gap-1" for="enableTableMapLink">
                                        <span>Map Linking</span>
                                        <i
                                            class="fas fa-info-circle text-muted ms-1"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="right"
                                            title="When enabled, searching this table will filter the linked map to only matching features."
                                            style="cursor: pointer;"
                                        ></i>
                                    </label>
                                </div>

                                <div id="tableMapLinkDropdown" style="display:none;">
                                    <label for="table_map_link_id">Select a map widget to link to</label>
                                    <select id="table_map_link_id" name="table_map_link_id" class="form-control mb-2">
                                        <option value="">Select a map...</option>
                                        @foreach ($mapWidgets as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        });
    </script>

    <script>
        $(function () {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            function update_axis_select(filename) {
                $.post('/profile/get-file-metadata', { filename }).done(function (response) {
                    $("#x_axis").empty();
                    $("#y_axis").empty();

                    $.each(response.x_axis || [], function(_, v) {
                        $('#x_axis').append(`<option value="${v}">${v}</option>`);
                    });

                    $('#y_axis').append('<option value="COUNT">COUNT</option>');
                    $.each(response.y_axis || [], function(_, v) {
                        $('#y_axis').append(`<option value="${v}">SUM ${v}</option>`);
                    });
                });
            }

            function update_table_select(filename) {
                $.post('/profile/get-file-metadata', { filename }).done(function (response) {
                    const $sel = $('#table_columns');
                    $sel.empty();

                    $.each(response.table_columns || [], function(i, value) {
                        $sel.append(`<option value="${value}">${value}</option>`);
                    });

                    $sel.trigger('change.select2');
                });
            }

        function update_legend_select(filename) {
            $.post('/profile/get-file-metadata', { filename }).done(function (response) {
                const $sel = $('#legend_property');
                const current = $sel.val() || '';

                $sel.empty();

                $.each(response.table_columns || [], function(_, value) {
                    $sel.append(`<option value="${value}">${value}</option>`);
                });

                if ($sel.find(`option[value="${current}"]`).length) {
                    $sel.val(current);
                }
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

                // Restore previous selection (string or array)
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

            function update_normalization_select(filename) {
                const $sel = $('#norm_form1');
                update_axis_select(filename);
                $sel.empty();
                $('#norm_form1').append(`<label for="vars">Select which variables to join</label><br>`);

                $.post('/profile/get-file-metadata', { filename }).done(function (response) {
                    $.each(response.y_axis || [], function(_, v) {
                        $('#norm_form1').append(`<input type="checkbox" name="norm_axis[]" value="${v}"> ${v} <br>`);
                    });
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
            // hide everything, then reveal only what we want.
            $("#map_forms").hide(0);
            $("#chart_forms").hide(0);
            $("#table_column").hide(0);
            if (t == 1) { // map
                $("#map_forms").show('slow');
                $("#map_popup_group").show('slow');
                // populate legend & popup selects for the currently chosen file
                update_legend_select($('#map_filename').val());
                update_popup_select($('#map_filename').val());
            } else if (t == 5) { // table
                $("#table_column").show('slow');
                update_table_select($('#map_filename').val());
            } else { // charts
                $("#chart_forms").show('slow');
                update_axis_select($('#map_filename').val());
            }
        });

        $('#map_filename').on('change', function () {
            const file = $(this).val();
            // refresh both sets; the visible block will show the right one
            update_axis_select(file);
            update_table_select(file);
            update_normalization_select(file);
            // also refresh legend and popup options when changing file
            update_legend_select(file);
            update_popup_select(file);
        });

        // initialize popup selector (show custom template area when chosen)
        $('#map_tooltip').on('change', function () {
            const val = $(this).val() || [];
            // if 'custom' is selected, show the custom template textarea
            if (Array.isArray(val) ? val.indexOf('custom') !== -1 : val === 'custom') {
                $('#popup_template_group').show('slow');
            } else {
                $('#popup_template_group').hide('slow');
            }
        });

        // make map_tooltip a nicer multi-select like table_columns
        $('#map_tooltip').select2({
            placeholder: 'Select popup properties',
            closeOnSelect: false,
            allowClear: true,
            width: '100%'
        });

            $('#norm_control').on('change', function () {
                const cont = $(this).val();

                if (cont == "NOPE") {
                    $("#x_axis_group").show('slow');
                    $("#y_axis_group").show('slow');
                    $("#norm_form1").hide('slow');
                    $("#norm_form").hide('slow');
                } else if (cont == "YEAH") {
                    $("#x_axis_group").hide('slow');
                    $("#y_axis_group").hide('slow');
                    $("#norm_form1").show('slow');
                    $("#norm_form").show('slow');
                    update_axis_select($('#map_filename').val());
                    update_normalization_select($('#map_filename').val());
                }
            });

        $('#widget_type').trigger('change');
    });
    </script>
@endpush