@extends('layouts.app')

@section('title', 'Employee Import Preview')
@section('page-title', 'Employee Import Preview')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('imports.index') }}">Imports / Exports</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $importBatch->reference_no }}</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        @if (! $importBatch->isFinal())
            @if ($importBatch->valid_rows > 0)
                <form method="POST" action="{{ route('imports.employees.confirm', $importBatch) }}" data-confirm="true" data-confirm-title="Confirm employee import?" data-confirm-message="Only valid rows will be imported. Invalid and duplicate rows will remain in the batch for review." data-confirm-button="Import valid rows">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-md">Confirm Import</button>
                </form>
            @endif
            <form method="POST" action="{{ route('imports.employees.cancel', $importBatch) }}" data-confirm="true" data-confirm-title="Cancel import batch?" data-confirm-message="This will mark the batch as cancelled. No employee records will be created." data-confirm-button="Cancel import" data-confirm-variant="btn-warning">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-secondary btn-md">Cancel Batch</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    @include('imports.partials.batch-summary', ['batch' => $importBatch])
    @include('imports.partials.rows-table', ['rows' => $rows])
@endsection
