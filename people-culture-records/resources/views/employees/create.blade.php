@extends('layouts.app')

@section('title', 'Create Employee')
@section('page-title', 'Create Employee')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('employees.store') }}" data-confirm="true" data-confirm-title="Create employee?" data-confirm-message="You are about to add this employee to the central staff register. Do you want to continue?" data-confirm-button="Create employee">
            @csrf
            @include('employees._form')
        </form>
    </div>
@endsection
