<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/** Pengguna baru membuat password sendiri lewat tautan, sehingga tidak ada password yang dikirim lewat email. */
class AktivasiAkunController extends Controller
{
    public const BERLAKU_HARI = 3;

    public static function tautan(User $user): string
    {
        return URL::temporarySignedRoute('aktivasi.create', now()->addDays(self::BERLAKU_HARI), [
            'user' => $user,
            'kunci' => self::kunci($user),
        ]);
    }

    /** Berasal dari password saat ini, sehingga tautan mati begitu password dibuat. */
    private static function kunci(User $user): string
    {
        return hash_hmac('sha256', $user->password, config('app.key'));
    }

    private function pastikanBerlaku(Request $request, User $user, string $kunci): void
    {
        abort_unless(
            $request->hasValidSignature() && hash_equals(self::kunci($user), $kunci),
            403,
            'Tautan aktivasi sudah tidak berlaku. Minta admin mengirim ulang, atau gunakan Lupa Password di halaman login.'
        );
    }

    public function create(Request $request, User $user, string $kunci): View
    {
        $this->pastikanBerlaku($request, $user, $kunci);

        return view('auth.aktivasi-akun', ['user' => $user, 'aksi' => $request->fullUrl()]);
    }

    public function store(Request $request, User $user, string $kunci): RedirectResponse
    {
        $this->pastikanBerlaku($request, $user, $kunci);

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Tautan dikirim ke email pengguna, jadi membukanya sekaligus membuktikan email itu miliknya.
        $user->forceFill([
            'password' => Hash::make($request->password),
            'wajib_ganti_sandi' => false,
            'remember_token' => Str::random(60),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Akun sudah aktif. Silakan masuk dengan NIP dan password baru Anda.');
    }
}
