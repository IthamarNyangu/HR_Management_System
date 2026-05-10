@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" data-confirm="true" data-confirm-title="Save user changes?" data-confirm-message="You are about to update this user's account, role, province, or active status. Do you want to continue?" data-confirm-button="Save changes">
            @csrf
            @method('PUT')
            @include('admin.users.partials.form')
        </form>
    </div>
@endsection
