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
                <div class="card-header">{{ __('New Dashboard') }}</div>
                <div class="card-body">
                    <form action="{{ route('profile.add-dashboard') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="name">Dashboard Name</label>
                            <input type="text" class="form-control" id="name" name="name" aria-describedby="titleHelp" placeholder="Enter name">
                            <small id="titleHelp" class="form-text text-muted pb-5">This is where you set the title for your new dashboard</small>
                            <button class="btn btn-primary" type="submit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
