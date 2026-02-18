@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('My Uploaded Files') }}</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="uploads_table" class="table table-striped table-border compact">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Filename</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($files as $file)
                                <tr>
                                    <td>{{$file['title']}}</td>
                                    <td>{{$file['filename']}}</td>
                                    <td>
                                        <form action="{{ route('profile.delete-upload') }}" method="POST" onsubmit="return confirm('Delete this file?');">
                                            @csrf
                                            <input type="hidden" name="filename" value="{{$file['filename']}}">
                                            <button type="submit" class="btn btn-danger rounded-sm fas fa-trash"></button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                @error('my_file')
                    <div>{{ $message }}</div>
                @enderror
                <div class="card-header">{{ __('Upload a GEO file') }}</div>
                <div class="card-body">
                    <form action="{{ route('profile.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="title">File Title</label>
                            <input type="title" class="form-control" id="title" name="title" aria-describedby="titleHelp" placeholder="Enter title">
                            <small id="titleHelp" class="form-text text-muted pb-5">This is where you make a title for the file you're uploading</small>
                            <input type="file" name="my_file">
                            <button type="submit">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row-mt4">
        <div class="col-md-3">
            <div class="card-body">
                <button>Connect to Postgres</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new DataTable('#uploads_table');
    });
</script>
@endsection
