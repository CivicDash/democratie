<?php

namespace App\Http\Requests\Auth;

use App\Services\IpBanService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const ABUSE_THRESHOLD = 3; // 3 verrouillages -> ban temporaire

    private const ABUSE_DECAY = 3600;  // 1 h

    private const BAN_BASE = 900;      // 15 min

    private const BAN_MAX = 86400;     // 24 h

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if ($this->isIpBanned()) {
            $seconds = RateLimiter::availableIn($this->banKey());
            $minutes = ceil($seconds / 60);

            throw ValidationException::withMessages([
                'email' => "Votre IP est temporairement bloquée. Réessayez dans {$minutes} minute(s).",
            ]);
        }

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $this->recordAbuse();

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    protected function abuseKey(): string
    {
        return 'login:abuse:'.$this->ip();
    }

    protected function banKey(): string
    {
        return 'login:ban:'.$this->ip();
    }

    protected function recordAbuse(): void
    {
        $count = RateLimiter::hit($this->abuseKey(), self::ABUSE_DECAY);
        if ($count < self::ABUSE_THRESHOLD) {
            return;
        }

        $level = max(0, $count - self::ABUSE_THRESHOLD);
        $banSeconds = min(self::BAN_BASE * (2 ** $level), self::BAN_MAX);
        RateLimiter::hit($this->banKey(), $banSeconds);

        app(IpBanService::class)->logBan(
            $this->ip(),
            'login',
            $this->banKey(),
            $this->abuseKey(),
            $count,
            $banSeconds,
            'Trop de tentatives de connexion'
        );
    }

    protected function isIpBanned(): bool
    {
        return RateLimiter::tooManyAttempts($this->banKey(), 1);
    }
}
