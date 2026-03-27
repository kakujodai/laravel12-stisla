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

                            {{-- Chart controls --}}
                            <div id="chart_forms" class="form-group" style="display:none;">
                                <label for="norm_control">Select whether or not to normalize multiple values.</label>
                                <select class="form-control mb-2" id="norm_control" name="norm_control">
                                    <option value="NOPE">Do not normalize</option>
                                    <option value="YEAH">Normalize multiple values</option>
                                </select>

                                <div id="x_axis_group" class="form-group" style="display:none;">
                                    <label for="x_axis">Select the property you want to use on the x axis.</label>
                                    <select class="form-control mb-2" id="x_axis" name="x_axis">
                                        <option value="">Loading..</option>
                                    </select>
                                </div>

                                <div id="y_axis_group" class="form-group" style="display:none;">
                                    <label for="y_axis">Select the property you want to use on the y axis.</label>
                                    <select class="form-control mb-2" id="y_axis" name="y_axis">
                                        <option value="">Loading..</option>
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
                                        <option value="">Loading..</option>
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

            $('#table_columns').select2({
                placeholder: 'Select table properties',
                closeOnSelect: false,
                allowClear: true,
                dropdownParent: $('.col-selector-wrap')
            });

            $('#widget_type').on('change', function () {
                const t = $(this).val();

                if (t == 1) {
                    $("#chart_forms").hide('slow');
                    $("#table_column").hide('slow');
                } else if (t == 5) {
                    $("#chart_forms").hide('slow');
                    $("#table_column").show('slow');
                    update_table_select($('#map_filename').val());
                } else {
                    $("#table_column").hide('slow');
                    $("#chart_forms").show('slow');
                    $("#norm_form").hide('slow');
                    update_axis_select($('#map_filename').val());
                }
            });

            $('#map_filename').on('change', function () {
                const file = $(this).val();
                update_axis_select(file);
                update_table_select(file);
                update_normalization_select(file);
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

        document.addEventListener("DOMContentLoaded", function () {
            const checkbox = document.getElementById("enableTableMapLink");
            const dropdown = document.getElementById("tableMapLinkDropdown");

            function toggleDropdown() {
                dropdown.style.display = checkbox.checked ? "block" : "none";
            }

            toggleDropdown();
            checkbox.addEventListener("change", toggleDropdown);
        });
    </script>
@endpush