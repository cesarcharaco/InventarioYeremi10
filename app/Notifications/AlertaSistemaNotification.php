<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AlertaSistemaNotification extends Notification
{
    use Queueable;

    public array $detalles;

    public function __construct(array $detalles)
    {
        $this->detalles = $detalles;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo'  => $this->detalles['titulo'] ?? 'Alerta del Sistema',
            'mensaje' => $this->detalles['mensaje'] ?? '',
            'url'     => $this->detalles['url'] ?? null,
            'icono'   => $this->detalles['icono'] ?? 'fas fa-bell text-info',
        ];
    }
}