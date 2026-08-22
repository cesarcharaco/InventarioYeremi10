<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Despachos;

class DespachoNotification extends Notification
{
    use Queueable;

    public $despacho;
    public $tipo; // Puede ser 'creado' o 'recibido'

    public function __construct(Despachos $despacho, string $tipo = 'creado')
    {
        $this->despacho = $despacho;
        $this->tipo = $tipo;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        // Cambiamos el mensaje dinámicamente según el momento del proceso
        if ($this->tipo === 'creado') {
            $mensaje = "Se ha emitido un nuevo despacho ({$this->despacho->codigo}) en tránsito hacia tu sucursal.";
        } else {
            $mensaje = "El despacho {$this->despacho->codigo} ha sido procesado en destino. Estado: {$this->despacho->estado}.";
        }

        return [
            'despacho_id' => $this->despacho->id,
            'codigo'      => $this->despacho->codigo,
            'estado'      => $this->despacho->estado,
            'tipo'        => $this->tipo,
            'mensaje'     => $mensaje,
        ];
    }
}