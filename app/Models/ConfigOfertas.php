<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigOfertas extends Model
{
    protected $table = 'config_ofertas';

    protected $fillable = [
        'id_local',
        'motivo',
        'criterio_fin',
        'id_caja_origen',
        'id_usuario_admin'
    ];

    protected $casts = [
        'estado' => 'boolean', // Esto transforma el 1 de la BD en true de PHP automáticamente
    ];

    // Relación con el Local (Nota: tu tabla se llama 'local')
    public function local(): BelongsTo {
        return $this->belongsTo(Local::class, 'id_local');
    }

    public function administrador(): BelongsTo {
        return $this->belongsTo(User::class, 'id_usuario_admin');
    }

    public function cajaOrigen(): BelongsTo {
        return $this->belongsTo(Caja::class, 'id_caja_origen');
    }

    /**
     * MÉTODO CORREGIDO
     */
    public static function obtenerActiva($idLocal)
    {
        if (!$idLocal) return null;

        $oferta = self::where('id_local', $idLocal)
                      ->where('estado', true)
                      ->first();

        if (!$oferta) return null;

        // Validación de cierre de caja
        if ($oferta->criterio_fin === 'cierre_caja' && $oferta->id_caja_origen) {
            // Cargamos la relación manualmente para verificar
            $oferta->load('cajaOrigen');
            
            if (!$oferta->cajaOrigen || $oferta->cajaOrigen->estado !== 'abierta') {
                $oferta->update(['estado' => false]);
                return null;
            }
        }

        return $oferta;
    }
}