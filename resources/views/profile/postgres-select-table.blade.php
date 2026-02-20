@extends('layouts.app')

@section('content')
<div class="main-content">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card">
                <div class="card-header">
                    {{ __('Select a Table') }}
                </div>

                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('profile.postgres.import') }}" method="POST">
                        @csrf

                        <input type="hidden" name="pg_host" value="{{ $connection['pg_host'] }}">
                        <input type="hidden" name="pg_port" value="{{ $connection['pg_port'] }}">
                        <input type="hidden" name="pg_database" value="{{ $connection['pg_database'] }}">
                        <input type="hidden" name="pg_username" value="{{ $connection['pg_username'] }}">
                        <input type="hidden" name="pg_password" value="{{ $connection['pg_password'] }}">
                        <input type="hidden" name="pg_sslmode" value="{{ $connection['pg_sslmode'] ?? 'prefer' }}">
                        <input type="hidden" name="schema" value="{{ $schema }}">

                        <div class="form-group mb-4">
                            <label for="table">Table</label>
                            <select name="table" class="form-control">
                                @foreach($tables as $table)
                                    <option value="{{ $table }}">{{ $table }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Import
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
