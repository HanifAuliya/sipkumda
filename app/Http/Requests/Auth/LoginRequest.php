<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Percobaan gagal per kombinasi NIP + IP sebelum dikunci sementara. */
    private const MAKS_PER_NIP = 5;

    /** Percobaan gagal per IP (semua NIP) dalam 15 menit, mencegah tebak password massal. */
    private const MAKS_PER_IP = 20;

    public function authorize(): bool
    {
        return true; // Semua pengguna diizinkan untuk mencoba login
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'NIP' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'NIP.required' => 'NIP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('NIP', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->ipThrottleKey(), 15 * 60);

            throw ValidationException::withMessages([
                'NIP' => 'NIP atau Password salah.',
            ]);
        }

        // Kunci per IP sengaja tidak dihapus agar tidak bisa di-reset dengan login ke akun sendiri.
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $key = match (true) {
            RateLimiter::tooManyAttempts($this->throttleKey(), self::MAKS_PER_NIP) => $this->throttleKey(),
            RateLimiter::tooManyAttempts($this->ipThrottleKey(), self::MAKS_PER_IP) => $this->ipThrottleKey(),
            default => null,
        };

        if (! $key) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'NIP' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('NIP')) . '|' . $this->ip());
    }

    public function ipThrottleKey(): string
    {
        return 'login-ip|' . $this->ip();
    }
}
