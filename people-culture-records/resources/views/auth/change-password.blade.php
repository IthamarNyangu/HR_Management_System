@extends('layouts.app')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Change Password</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <section class="bg-white border rounded-2 p-4">
                <h2 class="h5 mb-2">Set Your Own Password</h2>
                <p class="text-muted mb-4">Your account was created with a temporary password. Please set a private password before using the system.</p>

                <form method="POST" action="{{ route('password.change.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-md">Change Password</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('app.logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-md">Logout</button>
                </form>
            </section>
        </div>
    </div>
@endsection
