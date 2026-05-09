<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Name</label>
        <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user?->name) }}" required>
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user?->email) }}" required>
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">Password</label>
        <input id="password" name="password" type="password" class="form-control" {{ $user ? '' : 'required' }}>
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" {{ $user ? '' : 'required' }}>
    </div>

    <div class="col-md-6">
        <label for="role_id" class="form-label">Role</label>
        <select id="role_id" name="role_id" class="form-select" required>
            <option value="">Select role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $user?->role_id) === (string) $role->id)>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="province_id" class="form-label">Province</label>
        <select id="province_id" name="province_id" class="form-select">
            <option value="">All provinces / not assigned</option>
            @foreach ($provinces as $province)
                <option value="{{ $province->id }}" @selected((string) old('province_id', $user?->province_id) === (string) $province->id)>
                    {{ $province->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <div class="form-check">
            <input id="is_active" name="is_active" type="checkbox" class="form-check-input" value="1" @checked(old('is_active', $user?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save User</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>
