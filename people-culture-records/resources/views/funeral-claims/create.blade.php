@extends('layouts.app')
@section('title','Register Funeral Claim')@section('page-title','Register Funeral Claim')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('funeral-claims.index') }}">Funeral Claims</a></li><li class="breadcrumb-item active">Register</li>@endsection
@section('content')<form method="POST" action="{{ route('funeral-claims.store') }}" class="card border-0 shadow-sm"><div class="card-body">@csrf @include('funeral-claims._form')</div><div class="card-footer bg-white d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('funeral-claims.index') }}">Cancel</a><button class="btn btn-primary">Register Claim</button></div></form>@endsection
