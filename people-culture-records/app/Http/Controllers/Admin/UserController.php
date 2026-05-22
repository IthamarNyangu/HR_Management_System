<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Employee;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with(['role', 'province', 'employee'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create', $this->formData());
    }

    public function store(StoreUserRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->validated();
        $employee = $this->selectedEmployee($data['employee_id'] ?? null);

        if ($employee) {
            $data['name'] = $data['name'] ?: $employee->full_name;
            $data['email'] = $data['email'] ?: $employee->email;
            $data['province_id'] = $data['province_id'] ?: $employee->province_id;
        }

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['employee_id'] = $employee?->id;

        $user = User::create($data);

        $action = $employee ? 'user_created_from_employee' : 'user_created';
        $description = $employee
            ? "{$request->user()->name} created user {$user->email} from employee {$employee->display_name}."
            : "{$request->user()->name} created user {$user->email}.";

        $activity->log(
            $action,
            $description,
            $user,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $user->load(['employee.province', 'employee.department', 'employee.jobTitle']);

        return view('admin.users.edit', $this->formData($user) + compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->validated();
        $previousEmployeeId = $user->employee_id;
        $employee = $this->selectedEmployee($data['employee_id'] ?? null);

        if ($employee) {
            $data['province_id'] = $data['province_id'] ?: $employee->province_id;
        }

        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $data['employee_id'] = $employee?->id;

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.'])->withInput();
        }

        $user->update($data);

        if ($previousEmployeeId === null && $user->employee_id !== null) {
            $activity->log(
                'user_linked_to_employee',
                "{$request->user()->name} linked user {$user->email} to employee {$user->employee?->display_name}.",
                $user,
                user: $request->user(),
                request: $request,
            );
        } elseif ($previousEmployeeId !== null && $user->employee_id === null) {
            $activity->log(
                'user_unlinked_from_employee',
                "{$request->user()->name} unlinked user {$user->email} from an employee record.",
                $user,
                user: $request->user(),
                request: $request,
            );
        } elseif ($previousEmployeeId !== null && $user->employee_id !== null && (int) $previousEmployeeId !== (int) $user->employee_id) {
            $activity->log(
                'user_linked_to_employee',
                "{$request->user()->name} changed the employee link for user {$user->email} to {$user->employee?->display_name}.",
                $user,
                user: $request->user(),
                request: $request,
            );
        }

        $activity->log(
            'user_updated',
            "{$request->user()->name} updated user {$user->email}.",
            $user,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(Request $request, User $user, ActivityLogger $activity): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        $wasActive = $user->is_active;
        $user->update(['is_active' => ! $user->is_active]);
        $action = $wasActive ? 'user_deactivated' : 'user_activated';
        $verb = $wasActive ? 'deactivated' : 'activated';

        $activity->log(
            $action,
            "{$request->user()->name} {$verb} user {$user->email}.",
            $user,
            user: $request->user(),
            request: $request,
        );

        return back()->with('success', 'User status updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?User $user = null): array
    {
        $employees = Employee::query()
            ->with(['province', 'department', 'jobTitle', 'user'])
            ->where(function ($query) use ($user) {
                $query->whereDoesntHave('user');

                if ($user?->employee_id) {
                    $query->orWhere('id', $user->employee_id);
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return [
            'roles' => Role::where('is_active', true)->orderBy('name')->get(),
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'employees' => $employees,
        ];
    }

    private function selectedEmployee(mixed $employeeId): ?Employee
    {
        if (blank($employeeId)) {
            return null;
        }

        return Employee::with(['province', 'department', 'jobTitle'])->findOrFail($employeeId);
    }
}
