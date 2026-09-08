<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaSistema extends Model
{
    protected $table = 'auditoria_sistema';
    
    public $timestamps = false; // La tabla usa 'ejecutado_en' en lugar de created_at/updated_at

    protected $guarded = ['id'];

    // Relación con el usuario que ejecutó la acción
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
