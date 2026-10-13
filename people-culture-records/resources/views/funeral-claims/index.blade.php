@extends('layouts.app')
@section('title', 'Funeral Claims')
@section('page-title', 'Funeral Claims')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Funeral Claims</li>@endsection
@section('page-actions')<a href="{{ route('funeral-claims.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Register Claim</a>@endsection
@section('content')
<div class="card border-0 shadow-sm mb-3"><div class="card-body"><form class="row g-2">
    <div class="col-lg-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference, employee or deceased"></div>
    <div class="col-lg-2"><select name="status" class="form-select"><option value="">All statuses</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-lg-2"><select name="deceased_category" class="form-select"><option value="">All categories</option>@foreach($categories as $value=>$label)<option value="{{ $value }}" @selected(request('deceased_category')===$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-lg-2"><select name="province_id" class="form-select"><option value="">All provinces</option>@foreach($provinces as $province)<option value="{{ $province->id }}" @selected((string)request('province_id')===(string)$province->id)>{{ $province->name }}</option>@endforeach</select></div>
    <div class="col-lg-2 d-flex gap-2"><button class="btn btn-primary">Filter</button><a class="btn btn-outline-secondary" href="{{ route('funeral-claims.index') }}">Reset</a></div>
</form></div></div>
<div class="table-responsive data-table-wrap"><table class="table data-table align-middle"><thead><tr><th>Reference</th><th>Employee</th><th>Deceased</th><th>Category</th><th>Eligibility</th><th>Status</th><th>Notified</th><th>Action</th></tr></thead><tbody>
@forelse($claims as $claim)<tr><td class="fw-semibold">{{ $claim->reference_no }}</td><td>{{ $claim->employee->display_name }}</td><td>{{ $claim->deceased_name }}</td><td>{{ $categories[$claim->deceased_category] ?? $claim->deceased_category }}</td><td>{{ $claim->eligibilityStatuses()[$claim->eligibility_status] ?? $claim->eligibility_status }}</td><td>{{ $statuses[$claim->status] ?? $claim->status }}</td><td>{{ $claim->notification_date->format('d M Y') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('funeral-claims.show',$claim) }}">View</a></td></tr>
@empty<tr><td colspan="8" class="text-center text-muted py-5">No funeral claims found.</td></tr>@endforelse
</tbody></table></div><div class="mt-3">{{ $claims->links() }}</div>
@endsection
