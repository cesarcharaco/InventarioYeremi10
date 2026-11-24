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
        $base = [
            'titulo'  => $this->detalles['titulo'] ?? 'Alerta del Sistema',
            'mensaje' => $this->detalles['mensaje'] ?? '',
            'url'     => $this->detalles['url'] ?? null,
            'icono'   => $this->detalles['icono'] ?? 'fas fa-bell text-info',
        ];

        // Conserva cualquier clave adicional enviada en $detalles (ej. cliente_id, tramo, id_local, etc.)
        // sin sobrescribir las claves base ni alterar las llamadas que solo pasan los 4 campos estándar.
        $extras = array_diff_key($this->detalles, array_flip(['titulo', 'mensaje', 'url', 'icono']));

        return array_merge($base, $extras);
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}