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

class PropertyRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $rejectionReason;

    public function __construct(
        public User $user,
        public Property $property,
        string $reason
    ) {
        $this->rejectionReason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: 'BĐS của bạn đã bị từ chối - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.property-rejected',
            with: [
                'user' => $this->user,
                'property' => $this->property,
                'reason' => $this->rejectionReason,
                'userName' => $this->user->name,
                'propertyTitle' => $this->property->title,
                'propertyAddress' => $this->property->address,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
