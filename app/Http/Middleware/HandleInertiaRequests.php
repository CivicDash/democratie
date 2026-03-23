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
                    'display_name' => $request->user()->display_name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->roles->pluck('name')->toArray(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                    'is_public_figure' => $request->user()->profile?->is_public_figure ?? false,
                    // Champs élu
                    'is_verified_elu' => $request->user()->is_verified_elu ?? false,
                    'elu_type' => $request->user()->elu_type,
                    'elu_ref' => $request->user()->elu_ref,
                    // 2FA
                    'two_factor_enabled' => $request->user()->two_factor_enabled ?? false,
                    'should_enable_two_factor' => $request->user()->shouldEnableTwoFactor(),
                    'has_franceconnect' => $request->user()->franceconnect_sub !== null,
                    'is_demo_account' => $request->user()->isDemoAccount(),
                    'can_enable_two_factor' => $request->user()->canEnableTwoFactor(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'demo' => config('app.demo_mode') ? [
                'citizen_email' => config('app.demo_citizen_email'),
                'citizen_password' => config('app.demo_citizen_password'),
                'elu_email' => config('app.demo_elu_email'),
                'elu_password' => config('app.demo_elu_password'),
            ] : null,
        ];
    }
}
