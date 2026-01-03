<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
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
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->roles->pluck('name')->toArray(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                    // Champs élu
                    'is_verified_elu' => $request->user()->is_verified_elu ?? false,
                    'elu_type' => $request->user()->elu_type,
                    'elu_ref' => $request->user()->elu_ref,
                    // 2FA
                    'two_factor_enabled' => $request->user()->two_factor_enabled ?? false,
                    'should_enable_two_factor' => $request->user()->shouldEnableTwoFactor(),
                    'has_franceconnect' => $request->user()->franceconnect_sub !== null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
