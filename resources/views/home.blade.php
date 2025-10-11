@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                    <div class="card-header">{{ __('Your Dashboards') }}</div>
                    <div class="card-body">
                        <div class="table-responsive">
                        @if (count($dashboards) > 0)
                            <table id="dashboards_table" class="table table-striped compact">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Widget Count</th>
                                        <th class="dt-body-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dashboards as $dashboard)
                                    @php
                                    if (!isset($widget_counts[$dashboard['id']])) {
                                        $widget_count = 0;
                                    } else {
                                        $widget_count = $widget_counts[$dashboard['id']];
                                    }   
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('profile.dashboard', ['id' => $dashboard['id']]) }}">{{$dashboard['name']}}</a>
                                        </td>
                                        <td>
                                            {{ $widget_count }}
                                        </td>
                                        <td>
                                            <form action="{{ route('profile.delete-dashboard', ['id' => $dashboard['id']]) }}" method="POST" style="display: inline-block;">
								                @csrf
								                <button type="submit" class="btn btn-secondary rounded-sm fas fa-trash"></button>
							                </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <b>You don't appear to have dashboards. Please add one.</b>
                        @endif
                        </div>
                    </div>
		            <div class="card-footer text-right">
                   	    <a href="{{ route('profile.add-dashboard') }} " class="btn btn-primary">Add Dashboard</a>
                    </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        DataTable.type('num', 'className', 'dt-body-center');
        DataTable.type('num-fmt', 'className', 'dt-body-right');
        DataTable.type('date', 'className', 'dt-body-right');
        new DataTable('#dashboards_table', {
            order: [[1, 'desc']], // sort by widget count desc
            "columnDefs": [ {
                "targets": 2, // Disable sorting on the action column
                "orderable": false
            } ]
        });
    });
</script>
@endsection
