<section class="bg-white border rounded-2 p-3">
    <h2 class="h5 mb-3">Row Preview</h2>

    <div class="table-responsive data-table-wrap">
        <table class="table table-hover align-middle data-table">
            @if (($batch->import_type ?? null) === 'disciplinary_cases')
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Status</th>
                        <th>Employee</th>
                        <th>Location</th>
                        <th>Case Details</th>
                        <th>Dates</th>
                        <th>Errors / Warnings</th>
                    </tr>
                </thead>
            @else
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Status</th>
                        <th>Employee</th>
                        <th>Location</th>
                        <th>Job / Department</th>
                        <th>Errors</th>
                    </tr>
                </thead>
            @endif
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $data = $row->normalized_data ?? [];
                        $errors = $row->errors ?? [];
                        $warnings = $row->warnings ?? [];
                        $badge = match ($row->status) {
                            'valid' => 'text-bg-success',
                            'invalid' => 'text-bg-danger',
                            'duplicate' => 'text-bg-warning',
                            'imported' => 'text-bg-primary',
                            default => 'text-bg-light',
                        };
                    @endphp
                    @if (($batch->import_type ?? null) === 'disciplinary_cases')
                        <tr>
                            <td>{{ $row->row_number }}</td>
                            <td><span class="badge {{ $badge }}">{{ ucfirst($row->status) }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $data['employee_no'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['employee_name'] ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $data['province_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['district_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['facility_name'] ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $data['case_status_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['offence_category_name'] ?? 'No category' }}</div>
                                <div class="small text-muted">{{ $data['penalty_type_name'] ?? 'No penalty' }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 280px;">{{ $data['nature_of_offence'] ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $data['effective_date'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['expiry_date'] ?? 'No expiry' }}</div>
                            </td>
                            <td>
                                @if ($errors !== [])
                                    <ul class="mb-2 ps-3 small text-danger">
                                        @foreach ($errors as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($warnings !== [])
                                    <ul class="mb-0 ps-3 small text-warning">
                                        @foreach ($warnings as $warning)
                                            <li>{{ $warning }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($errors === [] && $warnings === [])
                                    <span class="text-muted small">No errors or warnings</span>
                                @endif
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $row->row_number }}</td>
                            <td><span class="badge {{ $badge }}">{{ ucfirst($row->status) }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $data['employee_no'] ?? '-' }}</div>
                                <div class="small text-muted">{{ trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) ?: '-' }}</div>
                                <div class="small text-muted">{{ $data['email'] ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $data['province_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['district_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['facility_name'] ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $data['job_title_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['department_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['project_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $data['employment_status_name'] ?? '-' }}</div>
                            </td>
                            <td>
                                @if ($errors !== [])
                                    <ul class="mb-0 ps-3 small">
                                        @foreach ($errors as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted small">No errors</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ ($batch->import_type ?? null) === 'disciplinary_cases' ? 7 : 6 }}" class="text-center text-muted py-4">No rows found in this batch.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $rows->links() }}
    </div>
</section>
