@extends('layouts.app')

@section('title', 'Edit '.$config['label'])
@section('page-title', 'Edit '.$config['label'])

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin Panel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.master-data.records', $type) }}">{{ $config['label'] }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <div class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('admin.master-data.update', [$type, $record->id]) }}">
            @csrf
            @method('PUT')
            @include('admin.master-data.partials.form')
        </form>
    </div>
@endsection
