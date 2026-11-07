<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root Blade template that renders the React application.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default with every page.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $wallet = $user?->wallet;

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'roles' => $user->roles->pluck('slug')->all(),
                    'profile_picture' => $user->profile_picture,
                    'account_status' => $user->account_status?->value,
                    'verification_status' => $user->verification?->status?->value,
                ] : null,
                'wallet' => $wallet ? [
                    'available_minor' => $wallet->available_minor,
                    'reserved_minor' => $wallet->reserved_minor,
                    'available' => $wallet->available()->toDecimalString(),
                    'reserved' => $wallet->reserved()->toDecimalString(),
                    'currency' => $wallet->currency,
                    'is_test_credit' => $wallet->is_test_credit,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
