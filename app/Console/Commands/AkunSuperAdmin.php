<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Jalan darurat dari server: membuat Super Admin pertama, atau memulihkan akun yang terkunci
 * (misalnya semua Admin lupa kata sandi dan email tidak bisa dipakai). Kata sandi sementara
 * ditampilkan sekali dan wajib diganti saat masuk.
 *
 *   php artisan akun:super-admin 197001012000031001 --nama="Nama Lengkap" --email=nama@hstkab.go.id
 *   php artisan akun:super-admin 197001012000031001        (akun yang sudah ada: buat kata sandi sementara)
 */
class AkunSuperAdmin extends Command
{
    protected $signature = 'akun:super-admin {nip : NIP akun} {--nama= : Nama lengkap, untuk akun baru} {--email= : Email, untuk akun baru}';

    protected $description = 'Buat Super Admin baru, atau beri kata sandi sementara pada akun yang terkunci';

    public function handle(): int
    {
        $nip = trim((string) $this->argument('nip'));
        $user = User::where('NIP', $nip)->first();

        if (! $user) {
            $nama = trim((string) $this->option('nama'));
            $email = Str::lower(trim((string) $this->option('email')));

            if ($nama === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error("NIP {$nip} belum terdaftar. Untuk membuat akun baru, isi juga --nama dan --email yang sah.");

                return self::FAILURE;
            }
            if (User::where('email', $email)->exists()) {
                $this->error("Email {$email} sudah dipakai akun lain.");

                return self::FAILURE;
            }

            $user = User::create(['nama_user' => $nama, 'NIP' => $nip, 'email' => $email, 'password' => Hash::make(Str::random(64))]);
            $user->assignRole(Role::findOrCreate('Super Admin'));
            $this->info("Akun Super Admin {$nama} dibuat.");
        } else {
            $this->info("Akun {$user->nama_user} ({$user->getRoleNames()->join(', ')}) ditemukan; kata sandinya diganti dengan kata sandi sementara.");
        }

        $sandi = collect(range(1, 3))->map(fn () => substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 4))->implode('-');

        $user->forceFill([
            'password' => Hash::make($sandi),
            'wajib_ganti_sandi' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'remember_token' => Str::random(60),
        ])->save();

        Log::notice('Kata sandi sementara dibuat dari server (akun:super-admin).', ['untuk' => $user->id]);

        $this->newLine();
        $this->line("  NIP                  : <options=bold>{$user->NIP}</>");
        $this->line("  Kata sandi sementara : <options=bold>{$sandi}</>");
        $this->newLine();
        $this->comment('Sampaikan langsung kepada pemilik akun. Kata sandi ini wajib diganti saat pertama masuk.');

        return self::SUCCESS;
    }
}
