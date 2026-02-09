<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OwnerPhoneRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PhoneRequestApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public OwnerPhoneRequest $request
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: 'Yêu cầu xem SĐT đã được duyệt - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.phone-request-approved',
            with: [
                'user' => $this->user,
                'phoneRequest' => $this->request,
                'property' => $this->request->property,
                'userName' => $this->user->name,
                'propertyTitle' => $this->request->property->title,
                'ownerPhone' => $this->request->property->owner_phone,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
