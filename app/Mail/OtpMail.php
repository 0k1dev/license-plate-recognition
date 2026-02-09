<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;
    public int $expiresInMinutes;

    public function __construct(
        public User $user,
        string $otp,
        int $expiresIn = 5
    ) {
        $this->otpCode = $otp;
        $this->expiresInMinutes = $expiresIn;
    }

    public function envelope(): Envelope
    {
        // 1. Try to load template from DB
        $template = \Visualbuilder\EmailTemplates\Models\EmailTemplate::where('key', 'otp-email')->first();

        $subject = $template
            ? $this->replacePlaceholders($template->subject)
            : 'Mã OTP Đặt Lại Mật Khẩu - ' . config('app.name');

        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        // 1. Try to load template from DB
        $template = \Visualbuilder\EmailTemplates\Models\EmailTemplate::where('key', 'otp-email')->first();
        $dbContent = null;

        if ($template) {
            $dbContent = $this->replacePlaceholders($template->content);
        }

        return new Content(
            view: 'emails.otp',
            with: [
                'user' => $this->user,
                'otp' => $this->otpCode,
                'expiresIn' => $this->expiresInMinutes,
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'dbContent' => $dbContent, // Pass DB content to view
            ],
        );
    }

    protected function replacePlaceholders(?string $text): string
    {
        if (!$text) return '';

        return str_replace(
            ['{{otp}}', '{{userName}}', '{{userEmail}}', '{{expiresIn}}'],
            [$this->otpCode, $this->user->name, $this->user->email, $this->expiresInMinutes],
            $text
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
