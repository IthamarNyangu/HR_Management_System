@extends('layouts.app')

@section('title', 'New Staff Relocation')
@section('page-title', 'New Staff Relocation')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-relocations.index') }}">Staff Relocations</a></li>
    <li class="breadcrumb-item active" aria-current="page">New Relocation</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('staff-relocations.store') }}" enctype="multipart/form-data" data-confirm="true" data-confirm-title="Create relocation?" data-confirm-message="This relocation will be added to the employee's movement history. Current employee location will only change if the checkbox is selected. Do you want to continue?" data-confirm-button="Create relocation">
            @csrf
            @include('staff-relocations._form')
        </form>
    </div>
@endsection
