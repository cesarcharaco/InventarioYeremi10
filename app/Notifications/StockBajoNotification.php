<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockBajoNotification extends Notification
{
    use Queueable;
    public $detalles;
    /**
     * Create a new notification instance.
     */
    public function __construct($detalles)
    {
        $this->detalles = $detalles;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
                'titulo'  => $this->detalles['titulo'],
                'mensaje' => $this->detalles['mensaje'],
                'url'     => $this->detalles['url'],
                'icono'   => $this->detalles['icono'] ?? 'fas fa-bell'
            ];
    }
}
