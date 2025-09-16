@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                    <div class="card-header">{{ __('Your Dashboards') }}</div>
                    <div class="card-body">
                        <div class="table-responsive">
			    @php 
				if (count($dashboards) > 0) {
			    @endphp
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dashboards as $dashboard)
                                    <tr>
                                        <td><a href="{{ route('profile.dashboard', ['id' => $dashboard['id']]) }}">{{$dashboard['name']}}</a></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
			    @php 
				} else {
				    echo"<b>You don't appear to have dashboards. Please add one</b>";
				}
			    @endphp
                        </div>
                    </div>
		   <div class="card-footer text-right">
                   	<a href="{{ route('profile.add-dashboard') }} " class="btn btn-primary">Add Dashboard</a>
                   </div>
            </div>
        </div>
    </div>
</div>
@endsection
