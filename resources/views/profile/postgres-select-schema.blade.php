@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card">
                <div class="card-header">
                    {{ __('Select a Schema') }}
                </div>

                <div class="card-body">

                    <form action="{{ route('profile.postgres.tables') }}" method="POST">
                        @csrf

                        <!-- keep connection info alive -->
                        <input type="hidden" name="pg_host" value="{{ $connection['pg_host'] }}">
                        <input type="hidden" name="pg_port" value="{{ $connection['pg_port'] }}">
                        <input type="hidden" name="pg_database" value="{{ $connection['pg_database'] }}">
                        <input type="hidden" name="pg_username" value="{{ $connection['pg_username'] }}">
                        <input type="hidden" name="pg_password" value="{{ $connection['pg_password'] }}">
                        <input type="hidden" name="pg_sslmode" value="{{ $connection['pg_sslmode'] ?? 'prefer' }}">

                        <div class="form-group mb-4">
                            <label for="schema">Schema</label>
                            <select name="schema" class="form-control">
                                @foreach($schemas as $schema)
                                    <option value="{{ $schema }}">{{ $schema }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Next
                        </button>

                        <a href="{{ route('profile.postgres.form') }}" class="btn btn-secondary">
                            Back
                        </a>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
