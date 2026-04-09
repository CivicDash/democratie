<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DolibarrService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     * Registration is restricted to validated Civis-Consilium members (Dolibarr).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'date_of_birth' => 'required|date|before_or_equal:'.now()->subYears(18)->toDateString(),
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'date_of_birth.required' => 'La date de naissance est obligatoire.',
            'date_of_birth.date' => 'La date de naissance n\'est pas valide.',
            'date_of_birth.before_or_equal' => 'Vous devez avoir au moins 18 ans pour vous inscrire.',
        ]);

        $dolibarr = new DolibarrService;
        $check = $dolibarr->validateRegistration($request->email, $request->date_of_birth);

        if (! $check['ok']) {
            throw ValidationException::withMessages([
                $check['error'] => $check['message'],
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'date_of_birth' => $request->date_of_birth,
            'password' => Hash::make($request->password),
        ]);

        $dolibarr->syncMemberToUser($user, $check['member']);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('verification.notice'));
    }
}
