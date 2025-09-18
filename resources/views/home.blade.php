@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                    <div class="card-header">{{ __('Your Dashboards') }}</div>
                    <div class="card-body">
                        <div class="table-responsive">
                        @if (count($dashboards) > 0)
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dashboards as $dashboard)
                                    <tr>
                                        <td>
                                            <a href="{{ route('profile.dashboard', ['id' => $dashboard['id']]) }}">{{$dashboard['name']}}</a>
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
                            <b>You don't appear to have dashboards. Please add one</b>"
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
@endsection
