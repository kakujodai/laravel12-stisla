@php
    $dashboard_name = $dashboard_info['name'];
@endphp
@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                @error('name')
                    <div>{{ $message }}</div>
                @enderror
                {{-- @success('name')
                    <div>{{ session('success') }}</div>
                @endsuccess --}}
                <div class="card-header">Add Widget to {{ $dashboard_name }}</div>
                <div class="card-body">
                    <form action="{{ route('profile.add-widgets', ['id' => $dashboard_info['id']]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="widget_name">Enter a widget name</label>
                            <input class="form-control mb-2" type="text" id="widget_name" name="widget_name"/>
                            <label for="widget_type">Select a widget type</label>
                            <select class="form-control mb-2" type="select" id="widget_type" name="widget_type">
                                @foreach ($widget_types as $widget_type)
                                <option value="{{ $widget_type['id'] }}">{{ $widget_type['name'] }}</option>
                                @endforeach
                            </select>
                            <label for="map_filename">Select a geojson file</label>
                            <select class="form-control mb-2" type="select" id="map_filename" name="map_filename">
                                @foreach ($files as $file)
                                <option value="{{ $file['filename'] }}">{{ $file['filename'] }}</option>
                                @endforeach
                            </select>
			    <div id="chart_forms" class="form-group" style='display:none;'>
                                <label for="x_axis">Select the property you want to use on the x axis.</label>
     			        <select class="form-control mb-2" type="select" id="x_axis" name="x_axis">
  			        </select>
                                <label for="y_axis">Select the property you want to use on the y axis.</label>
			        <select class="form-control mb-2" type="select" id="y_axis" name="y_axis">
			        </select>
			    </div>
                            <button class="btn btn-primary" type="submit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
  });
  function update_axis_select(filename) {
        $.ajax({
           type: 'POST',
           url: '/profile/get-file-metadata',
           data: { 'filename': filename },
           success: function(response) {
	     $("#x_axis").empty(); // Nuke the current select options
             $("#y_axis").empty(); // Nuke the current select options
             $.each(response.x_axis, function(key, value) {
                 $('#x_axis').append('<option value="' + value + '">' + value + '</option>');
             });
             $('#y_axis').append('<option value="COUNT">COUNT</option>');
             $.each(response.y_axis, function(key, value) {
                 $('#y_axis').append('<option value="' + value + '">SUM ' + value + '</option>');
             });
	   },
           error: function(error) {
             console.error(error);
           }
        }); 
  }
  $('#widget_type').change(function() {
    if ($(this).val() != 1) { // if the value changes from map to a chart widget, show x and y axis forms
        $("#chart_forms").show("slow");
        var mapfilename = $('#map_filename').val()
        update_axis_select(mapfilename);
    } else {
        $("#chart_forms").hide("slow");
    }
  });
  $('#map_filename').change(function() { // check for changing map filename
      update_axis_select($(this).val());
  });
  
});
</script>
@endPush

