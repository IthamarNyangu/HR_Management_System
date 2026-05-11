@extends('layouts.app')

@section('title', 'New Disciplinary Case')
@section('page-title', 'New Disciplinary Case')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('disciplinary-cases.index') }}">Disciplinary Cases</a></li>
    <li class="breadcrumb-item active" aria-current="page">New Case</li>
@endsection

@section('content')
    <section class="bg-white border rounded-2 p-4">
        <form method="POST" action="{{ route('disciplinary-cases.store') }}" enctype="multipart/form-data">
            @csrf
            @include('disciplinary-cases._form')
        </form>
    </section>
@endsection
