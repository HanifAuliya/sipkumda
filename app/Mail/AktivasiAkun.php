<?php

namespace App\Mail;

use App\Http\Controllers\Auth\AktivasiAkunController;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AktivasiAkun extends Mailable
{
    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Aktivasi Akun SIFKUMDA');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.aktivasi-akun', with: [
            'tautan' => AktivasiAkunController::tautan($this->user),
            'berlakuHari' => AktivasiAkunController::BERLAKU_HARI,
        ]);
    }
}
