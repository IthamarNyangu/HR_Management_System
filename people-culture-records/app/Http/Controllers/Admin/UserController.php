<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
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
            ->with(['role', 'province'])
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
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);

        $activity->log(
            'user_created',
            "{$request->user()->name} created user {$user->email}.",
            $user,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', $this->formData() + compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.'])->withInput();
        }

        $user->update($data);

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
    private function formData(): array
    {
        return [
            'roles' => Role::where('is_active', true)->orderBy('name')->get(),
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
