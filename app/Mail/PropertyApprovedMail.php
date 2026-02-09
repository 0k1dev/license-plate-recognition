<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Property;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertyApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Property $property
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: 'BĐS của bạn đã được duyệt - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.property-approved',
            with: [
                'user' => $this->user,
                'property' => $this->property,
                'userName' => $this->user->name,
                'propertyTitle' => $this->property->title,
                'propertyAddress' => $this->property->address,
                'propertyPrice' => $this->property->price ? number_format((float) $this->property->price) : 'N/A',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
