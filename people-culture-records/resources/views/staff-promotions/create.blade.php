@extends('layouts.app')

@section('title', 'New Staff Promotion')
@section('page-title', 'New Staff Promotion')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-promotions.index') }}">Staff Promotions</a></li>
    <li class="breadcrumb-item active" aria-current="page">New Promotion</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('staff-promotions.store') }}" enctype="multipart/form-data" data-confirm="true" data-confirm-title="Create promotion?" data-confirm-message="This promotion will be added to the employee's historical record. Do you want to continue?" data-confirm-button="Create promotion">
            @csrf
            @include('staff-promotions._form')
        </form>
    </div>
@endsection
