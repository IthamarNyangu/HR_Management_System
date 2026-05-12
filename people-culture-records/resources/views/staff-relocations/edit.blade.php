@extends('layouts.app')

@section('title', 'Edit '.$relocation->reference_no)
@section('page-title', 'Edit Staff Relocation')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-relocations.index') }}">Staff Relocations</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-relocations.show', $relocation) }}">{{ $relocation->reference_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('staff-relocations.update', $relocation) }}" enctype="multipart/form-data" data-confirm="true" data-confirm-title="Save relocation changes?" data-confirm-message="You are about to update this relocation record. Employee current location will only change if the checkbox is selected. Do you want to continue?" data-confirm-button="Save changes">
            @csrf
            @method('PUT')
            @include('staff-relocations._form')
        </form>
    </div>
@endsection
