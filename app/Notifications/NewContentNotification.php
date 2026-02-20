<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewContentNotification extends Notification
{
    use Queueable;

    protected $metadata;

    /**
     * Create a new notification instance.
     * 
     * @param array $metadata {title, message, icon, color, link}
     */
    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => $this->metadata['title'] ?? 'New Update',
            'message' => $this->metadata['message'] ?? '',
            'icon' => $this->metadata['icon'] ?? 'Bell',
            'color' => $this->metadata['color'] ?? 'blue',
            'link' => $this->metadata['link'] ?? null,
        ];
    }
}
