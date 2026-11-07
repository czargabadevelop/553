<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Support\MoneyInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('profile', 'verification');

        return Inertia::render('Player/Profile', [
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'account_status' => $user->account_status?->value,
                'verification_status' => $user->verification?->status?->value ?? 'unverified',
                'email_verified' => $user->email_verified_at !== null,
            ],
            'profile' => [
                'display_name' => $user->profile?->display_name,
                'date_of_birth' => $user->profile?->date_of_birth?->toDateString(),
                'country_code' => $user->profile?->country_code,
                'phone' => $user->profile?->phone,
                'timezone' => $user->profile?->timezone,
                'deposit_limit' => $user->profile?->deposit_limit_minor
                    ? number_format($user->profile->deposit_limit_minor / 100, 2)
                    : null,
                'session_limit_minutes' => $user->profile?->session_limit_minutes,
                'self_excluded' => $user->profile?->self_excluded_at !== null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'profile.display_name' => ['nullable', 'string', 'max:64'],
            'profile.date_of_birth' => ['nullable', 'date', 'before:today'],
            'profile.country_code' => ['nullable', 'string', 'size:2'],
            'profile.phone' => ['nullable', 'string', 'max:32'],
            'profile.deposit_limit' => ['nullable', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'profile.session_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'profile.self_excluded' => ['boolean'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $profile = PlayerProfile::query()->firstOrCreate(['user_id' => $user->id]);

        $profile->fill([
            'display_name' => $validated['profile']['display_name'] ?? null,
            'date_of_birth' => $validated['profile']['date_of_birth'] ?? null,
            'country_code' => isset($validated['profile']['country_code']) ? strtoupper($validated['profile']['country_code']) : null,
            'phone' => $validated['profile']['phone'] ?? null,
            'deposit_limit_minor' => filled($validated['profile']['deposit_limit'] ?? null)
                ? MoneyInput::toMinor($validated['profile']['deposit_limit'])
                : null,
            'session_limit_minutes' => $validated['profile']['session_limit_minutes'] ?? null,
        ]);

        if (($validated['profile']['self_excluded'] ?? false) && $profile->self_excluded_at === null) {
            $profile->self_excluded_at = now();
        } elseif (! ($validated['profile']['self_excluded'] ?? false)) {
            $profile->self_excluded_at = null;
        }

        $profile->save();

        return back()->with('success', 'Profile updated.');
    }
}
