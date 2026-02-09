<?php

declare(strict_types=1);
namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;

class LogSentMessage
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        $to = [];
        foreach ($message->getTo() as $address) {
            $to[] = $address->getAddress();
        }

        \App\Models\EmailLog::create([
            'to' => implode(', ', $to),
            'subject' => $message->getSubject(),
            'content' => $message->getHtmlBody(), // Or getTextBody()
            'status' => 'sent',
            // 'template_key' => ... (Khó lấy được key từ event trừ khi pass qua header)
            'user_id' => auth()->id(), // Nếu gửi từ request có auth
        ]);
    }
}
