<?php

namespace App\Notifications\Channels;

use App\Notifications\GenericCrmNotification;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Notifications\Notification;

class ExpoPushChannel
{
    public function __construct(protected ExpoPushService $push)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof GenericCrmNotification) {
            return;
        }

        if (! method_exists($notifiable, 'id')) {
            return;
        }

        $this->push->sendToUser(
            $notifiable,
            $notification->title,
            $notification->message,
            array_filter([
                'url' => $notification->url,
                'category' => $notification->category,
                'lead_id' => $notification->meta['lead_id'] ?? null,
                'task_id' => $notification->meta['task_id'] ?? null,
            ])
        );
    }
}
