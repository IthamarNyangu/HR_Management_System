@extends('layouts.app')

@section('title', 'Edit '.$promotion->reference_no)
@section('page-title', 'Edit Staff Promotion')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-promotions.index') }}">Staff Promotions</a></li>
    <li class="breadcrumb-item"><a href="{{ route('staff-promotions.show', $promotion) }}">{{ $promotion->reference_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('staff-promotions.update', $promotion) }}" enctype="multipart/form-data" data-confirm="true" data-confirm-title="Save promotion changes?" data-confirm-message="You are about to update this promotion record. Already-applied historical promotions will not re-update the employee profile. Do you want to continue?" data-confirm-button="Save changes">
            @csrf
            @method('PUT')
            @include('staff-promotions._form')
        </form>
    </div>
@endsection
