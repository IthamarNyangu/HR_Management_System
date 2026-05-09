@extends('layouts.app')

@section('title', 'Create '.$config['label'])
@section('page-title', 'Create '.$config['label'])

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin Panel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.master-data.records', $type) }}">{{ $config['label'] }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('admin.master-data.store', $type) }}">
            @csrf
            @include('admin.master-data.partials.form', ['record' => null])
        </form>
    </div>
@endsection
