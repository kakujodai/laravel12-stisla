@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                    <div class="card-header">{{ __('My Uploaded Files') }}</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Filename</th>
					<th>Chart Metadata</th>
                                        <th>Md5</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($files as $file)
                                    <tr>
                                        <td>{{$file['title']}}</td>
                                        <td>{{$file['filename']}}</td>
					<td>{{$file['properties_metadata']}}</td>
                                        <td>{{$file['md5']}}</td>
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
                            <label for="exampleInputEmail1">File Title</label>
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
</div>
@endsection
