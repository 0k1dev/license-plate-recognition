<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[BĐS] Mã OTP đặt lại mật khẩu',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-otp',
            with: [
                'name' => $this->name,
                'otp'  => $this->otp,
                'ttl'  => 10,
            ],
        );
    }
}
