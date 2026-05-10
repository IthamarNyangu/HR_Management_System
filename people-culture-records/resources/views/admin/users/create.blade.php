@extends('layouts.app')

@section('title', 'Create User')
@section('page-title', 'Create User')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('admin.users.store') }}" data-confirm="true" data-confirm-title="Create user?" data-confirm-message="You are about to create a new system user with the selected role and access level. Do you want to continue?" data-confirm-button="Create user">
            @csrf
            @include('admin.users.partials.form', ['user' => null])
        </form>
    </div>
@endsection
