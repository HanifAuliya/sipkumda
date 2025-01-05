<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_user' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'lowercase',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'password' => [
                'nullable', // Tidak wajib jika tidak diubah
                'confirmed', // Harus ada password_confirmation
                'min:8', // Minimal 8 karakter
                'regex:/[A-Z]/', // Minimal 1 huruf besar
                'regex:/[a-z]/', // Minimal 1 huruf kecil
                'regex:/[0-9]/', // Minimal 1 angka
            ],
        ];
    }
}
