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
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Semua pengguna diizinkan untuk mencoba login
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'combined_fields' => function ($attribute, $value, $fail) {
                if (empty($this->NIP) || empty($this->password)) {
                    $fail('NIP dan Password wajib diisi.');
                }
            },
        ];
    }


    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'NIP.exists' => 'NIP atau Password salah.', // Ganti pesan default untuk validasi 'exists'
            'NIP.required' => 'NIP wajib diisi.',       // Pesan jika NIP kosong
            'password.required' => 'Password wajib diisi.', // Pesan jika password kosong
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

        // Autentikasi berdasarkan NIP dan password
        if (! Auth::attempt($this->only('NIP', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Tampilkan pesan jika login gagal
            throw ValidationException::withMessages([
                'NIP' => 'NIP atau Password salah.', // Pesan login gagal
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
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) { // Maks 5 percobaan login
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'NIP' => trans('auth.throttle', [
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
        return Str::transliterate(Str::lower($this->string('NIP')) . '|' . $this->ip()); // Gunakan NIP untuk identifikasi throttle
    }
}
