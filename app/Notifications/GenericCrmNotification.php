<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenericCrmNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public ?string $category = null,
        public array $meta = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'expo_push'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->line($this->message);
        if ($this->url) {
            $mail->action('Open GrowLead', $this->url);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'category' => $this->category,
            'lead_id' => $this->meta['lead_id'] ?? null,
            'task_id' => $this->meta['task_id'] ?? null,
        ];
    }
}
