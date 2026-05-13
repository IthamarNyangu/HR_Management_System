<section class="bg-white border rounded-2 p-3">
    <h2 class="h5 mb-3">Row Preview</h2>

    <div class="table-responsive data-table-wrap">
        <table class="table table-hover align-middle data-table">
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
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $data = $row->normalized_data ?? [];
                        $errors = $row->errors ?? [];
                        $badge = match ($row->status) {
                            'valid' => 'text-bg-success',
                            'invalid' => 'text-bg-danger',
                            'duplicate' => 'text-bg-warning',
                            'imported' => 'text-bg-primary',
                            default => 'text-bg-light',
                        };
                    @endphp
                    <tr>
                        <td>{{ $row->row_number }}</td>
                        <td><span class="badge {{ $badge }}">{{ ucfirst($row->status) }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $data['employee_no'] ?? '-' }}</div>
                            <div class="small text-muted">{{ trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) ?: '-' }}</div>
                            <div class="small text-muted">{{ $data['email'] ?? '-' }}</div>
                        </td>
                        <td>
                            <div>Province ID: {{ $data['province_id'] ?? '-' }}</div>
                            <div class="small text-muted">District ID: {{ $data['district_id'] ?? '-' }}</div>
                            <div class="small text-muted">Facility ID: {{ $data['facility_id'] ?? '-' }}</div>
                        </td>
                        <td>
                            <div>Job Title ID: {{ $data['job_title_id'] ?? '-' }}</div>
                            <div class="small text-muted">Department ID: {{ $data['department_id'] ?? '-' }}</div>
                            <div class="small text-muted">Project ID: {{ $data['project_id'] ?? '-' }}</div>
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
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No rows found in this batch.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $rows->links() }}
    </div>
</section>
