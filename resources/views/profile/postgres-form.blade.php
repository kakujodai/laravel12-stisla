@extends('layouts.app')

@section('content')
<div class="main-content">
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                {{ __('About PostgreSQL Imports') }}
            </div>
            <div class="card-body">
                <p>
                    Use this form to connect to an external PostgreSQL or PostGIS
                    database and import GIS layers into your dashboard. Easydash values
                    the security of your information, and therefore does not store
                    the information you input into these fields beyond connecting
                    to your database.
                </p>

                <p>
                    You will need:
                </p>

                <ul>
                    <li>Host</li>
                    <li>Port (usually 5432)</li>
                    <li>Database name</li>
                    <li>Username & password</li>
                </ul>
            </div>
        </div>

    </div>

    <div class="col-md-8">


            <div class="card">
                <div class="card-header">{{ __('Postgres Database Connection Form') }}</div>

                <div class="card-body">

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('profile.postgres.schemas') }}" method="POST">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="pg_host">Host</label>
                            <input type="text" class="form-control" id="pg_host" name="pg_host">
                        </div>

                        <div class="form-group mb-3">
                            <label for="pg_port">Port</label>
                            <input type="number" class="form-control" id="pg_port" name="pg_port">
                        </div>

                        <div class="form-group mb-3">
                            <label for="pg_database">Database</label>
                            <input type="text" class="form-control" id="pg_database" name="pg_database">
                        </div>

                        <div class="form-group mb-3">
                            <label for="pg_username">Username</label>
                            <input type="text" class="form-control" id="pg_username" name="pg_username">
                        </div>

                        <div class="form-group mb-3">
                            <label for="pg_password">Password</label>
                            <input type="password" class="form-control" id="pg_password" name="pg_password">
                        </div>

                        <div class="form-group mb-4">
                            <label for="pg_sslmode">SSL Mode</label>
                            <select class="form-control" id="pg_sslmode" name="pg_sslmode">
                                <option value="prefer" {{ old('pg_sslmode', 'prefer') === 'prefer' ? 'selected' : '' }}>prefer</option>
                                <option value="require" {{ old('pg_sslmode') === 'require' ? 'selected' : '' }}>require</option>
                                <option value="disable" {{ old('pg_sslmode') === 'disable' ? 'selected' : '' }}>disable</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Connect</button>
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
