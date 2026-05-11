@extends('layouts.app')

@section('title', 'Edit '.$case->reference_no)
@section('page-title', 'Edit Disciplinary Case')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('disciplinary-cases.index') }}">Disciplinary Cases</a></li>
    <li class="breadcrumb-item"><a href="{{ route('disciplinary-cases.show', $case) }}">{{ $case->reference_no }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('disciplinary-cases.update', $case) }}">
            @csrf
            @method('PUT')
            @include('disciplinary-cases._form')
        </form>
    </section>
@endsection
