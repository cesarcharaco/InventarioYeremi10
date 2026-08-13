<?php

namespace App\Observers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AuditoriaObserver
{
    public function updated($model): void
    {
        // Detecta qué campos cambiaron para no guardar todo el json innecesariamente
        $valoresAnteriores = array_intersect_key($model->getOriginal(), $model->getChanges());
        $valoresNuevos = $model->getChanges();

        // Excluir campos de tiempo comunes
        unset($valoresAnteriores['updated_at'], $valoresNuevos['updated_at']);

        if (!empty($valoresNuevos)) {
            DB::table('auditoria_sistema')->insert([
                'tabla_afectada' => $model->getTable(),
                'accion'         => 'UPDATE',
                'registro_id'    => $model->getKey(),
                'valores_anteriores' => json_encode($valoresAnteriores),
                'valores_nuevos'     => json_encode($valoresNuevos),
                'id_user'        => Auth::id(), // Captura automáticamente al usuario logueado
                'ejecutado_en'   => now(),
            ]);
        }
    }

    public function created($model): void
    {
        DB::table('auditoria_sistema')->insert([
            'tabla_afectada' => $model->getTable(),
            'accion'         => 'INSERT',
            'registro_id'    => $model->getKey(),
            'valores_anteriores' => null,
            'valores_nuevos'     => json_encode($model->getAttributes()),
            'id_user'        => Auth::id(),
            'ejecutado_en'   => now(),
        ]);
    }
}