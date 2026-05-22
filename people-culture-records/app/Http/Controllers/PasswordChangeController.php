<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordChangeController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        $activity->log(
            'password_changed',
            "{$user->name} changed their temporary password.",
            $user,
            user: $user,
            request: $request,
        );

        return redirect()->route('dashboard')->with('success', 'Password changed successfully.');
    }
}
