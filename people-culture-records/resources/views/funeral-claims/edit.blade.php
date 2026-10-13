@extends('layouts.app')
@section('title','Edit Funeral Claim')@section('page-title','Edit '.$claim->reference_no)
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('funeral-claims.index') }}">Funeral Claims</a></li><li class="breadcrumb-item active">{{ $claim->reference_no }}</li>@endsection
@section('content')<form method="POST" action="{{ route('funeral-claims.update',$claim) }}" class="card border-0 shadow-sm"><div class="card-body">@csrf @method('PUT') @include('funeral-claims._form')</div><div class="card-footer bg-white d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('funeral-claims.show',$claim) }}">Cancel</a><button class="btn btn-primary">Save and Recalculate</button></div></form>@endsection
