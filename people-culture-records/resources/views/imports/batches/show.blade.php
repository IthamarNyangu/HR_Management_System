@extends('layouts.app')

@section('title', 'Import Batch')
@section('page-title', 'Import Batch '.$importBatch->reference_no)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('imports.index') }}">Imports / Exports</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $importBatch->reference_no }}</li>
@endsection

@section('content')
    @include('imports.partials.batch-summary', ['batch' => $importBatch])
    @include('imports.partials.rows-table', ['rows' => $rows, 'batch' => $importBatch])
@endsection
