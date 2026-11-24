<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Despachos;

class DespachoNotification extends Notification
{
    use Queueable;

    public $despacho;
    public $tipo; // 'creado', 'recibido' o 'solicitud_creada'
    public ?string $url;

    public function __construct(Despachos $despacho, string $tipo = 'creado', ?string $url = null)
    {
        $this->despacho = $despacho;
        $this->tipo = $tipo;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $codigo = $this->despacho->codigo ?? ('#' . $this->despacho->id);
        $estado = $this->despacho->estado ?? '';

        if ($this->tipo === 'solicitud_creada') {
            $titulo = 'Nueva Solicitud de Pedido';
            $mensaje = "Se ha registrado la solicitud {$codigo} pendiente por despacho.";
            $icono = 'fas fa-clipboard-list text-warning';
        } elseif ($this->tipo === 'recibido') {
            $titulo = 'Despacho Procesado en Destino';
            $mensaje = "El despacho {$codigo} ha sido procesado en destino. Estado: {$estado}.";
            $icono = 'fas fa-check-circle text-success';
        } else {
            $titulo = 'Nuevo Despacho en Tránsito';
            $mensaje = "Se ha emitido un nuevo despacho ({$codigo}) en tránsito hacia tu sucursal.";
            $icono = 'fas fa-truck text-primary';
        }

        // Si se pasa una URL explícita desde el controlador se usa; si no, abre el historial con el detalle del despacho
        $destinoUrl = $this->url ?? route('despacho.index', ['ver_detalle' => $this->despacho->id]);

        return [
            'despacho_id' => $this->despacho->id,
            'codigo'      => $this->despacho->codigo,
            'estado'      => $this->despacho->estado,
            'tipo'        => $this->tipo,
            'titulo'      => $titulo,
            'mensaje'     => $mensaje,
            'url'         => $destinoUrl,
            'icono'       => $icono,
        ];
    }
}