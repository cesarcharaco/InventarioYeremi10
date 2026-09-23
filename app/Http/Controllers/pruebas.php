<?php


public function solicitarPin(Request $request)
{
    $user = Auth::user();
    $local = $user->localActual();

    // 1. Validar que el local exista
    if (!$local) {
        return response()->json([
            'success' => false, 
            'message' => 'No se encontró un local activo para solicitar el PIN.'
        ], 422);
    }

    // Generar PIN plano de 6 dígitos
    $pinPlano = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // 2. Guardar o actualizar la solicitud del local encriptando el PIN
    AutorizacionPin::updateOrCreate(
        [
            'id_local' => $local->id,
        ],
        [
            'vendedor'   => $user->name, 
            'pin'        => Hash::make($pinPlano), // <-- Encriptado correctamente
            'monto'      => $request->monto_total,
            'cliente'    => $request->cliente_nombre,
            'estado'     => 'activo',
            'updated_at' => now()
        ]
    );

    // 3. Obtener los IDs de usuarios asignados al local ($local->id en vez de $venta->id_local)
    $usuariosLocalIds = DB::table('users_has_local')
        ->where('id_local', $local->id)
        ->pluck('id_user');

    // 4. Buscar administradores y encargados
    $destinatarios = User::where('role', 'admin')
        ->orWhere(function($query) use ($usuariosLocalIds) {
            $query->whereIn('role', ['encargado'])
                  ->whereIn('id', $usuariosLocalIds);
        })
        ->get();

    // 5. Incluir el PIN en texto plano dentro del mensaje enviado al jefe
    $detalles = [
        'titulo'  => '🔐 Solicitud de PIN de Autorización',
        'mensaje' => "{$user->name} en {$local->nombre} solicita PIN para venta de {$request->monto_total}$. Código PIN: {$pinPlano}",
        'url'     => '#', 
        'icono'   => 'fas fa-key text-warning'
    ];

    foreach ($destinatarios as $destinatario) {
        $destinatario->notify(new StockBajoNotification($detalles));
    }

    return response()->json([
        'success' => true, 
        'message' => 'PIN generado y enviado exitosamente al encargado/administrador.'
    ]);
}

public function verificarPin(Request $request)
{
    $user = Auth::user();
    $local = $user->localActual();

    if (!$local) {
        return response()->json([
            'success' => false, 
            'message' => 'No se encontró un local activo para validar el PIN.'
        ], 422);
    }

    $pinIngresado = trim($request->pin);

    if (empty($pinIngresado)) {
        return response()->json([
            'success' => false, 
            'message' => 'Debe ingresar el código PIN.'
        ], 422);
    }

    return DB::transaction(function () use ($local, $pinIngresado) {
        $auth = AutorizacionPin::where('id_local', $local->id)
                    ->where('estado', 'activo')
                    ->lockForUpdate()
                    ->latest('updated_at')
                    ->first();

        // Compara el PIN ingresado con el Hash guardado en la base de datos
        if ($auth && Hash::check($pinIngresado, $auth->pin)) {
            $auth->update(['estado' => 'usado']); 
            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'El PIN de autorización no es válido, ya fue utilizado o expiró.'
        ], 422);
    });
}


public function pdfEstadoCuenta($cliente_id)
    {
        $cliente = Cliente::findOrFail($cliente_id);

        // Créditos ordenados cronológicamente de más antiguo a más reciente (Solo pendientes y anticipos)
        $creditos = Credito::where('id_cliente', $cliente_id)
            ->whereIn('estado', ['pendiente', 'anticipo'])
            ->with([
                'venta.detalles.insumo',
                'intereses' => function($q) {
                    $q->where('estado', 'aplicado')
                      ->orderBy('aplicado_en', 'asc'); // Ordenar indexaciones por fecha
                },
                'abonos' => function($q) {
                    $q->where('abonos_credito.estado', 'Realizado')
                      ->orderBy('abonos_credito.created_at', 'asc'); // Ordenar abonos por fecha
                }
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        $creditosIds = $creditos->pluck('id');

        $historialIntereses = CreditoInteres::whereIn('id_credito', $creditosIds)
            ->where('estado', 'aplicado')
            ->orderBy('aplicado_en', 'asc')
            ->get();

        $montoInicialTotal = $creditos->where('estado', 'pendiente')->sum('monto_inicial');
        $totalIntereses = $historialIntereses->sum('monto_interes');

        // Sumatoria exacta desde la tabla pivote abono_detalles
        $totalAbonado = AbonoDetalle::whereIn('id_credito', $creditosIds)
            ->whereHas('abono', function($q) {
                $q->where('estado', '!=', 'Anulado');
            })
            ->sum('monto_aplicado_usd');

        $saldoPendienteTotal = $creditos
            ->where('estado', 'pendiente')
            ->sum('saldo_pendiente');

        $saldoAFavorTotal = abs($creditos
            ->where('estado', 'anticipo')
            ->sum('saldo_pendiente'));

        $resumen = [
            'monto_inicial'   => $montoInicialTotal,
            'total_intereses' => $totalIntereses,
            'total_abonado'   => $totalAbonado,
            'saldo_a_favor'   => $saldoAFavorTotal,
            'saldo_pendiente' => $saldoPendienteTotal,
            'neto_a_pagar'    => max(0, $saldoPendienteTotal - $saldoAFavorTotal)
        ];

        $empresa = Local::first();

        $pdf = Pdf::loadView('creditos.pdf.estado_cuenta', compact(
            'cliente',
            'creditos',
            'resumen',
            'empresa'
        ));

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Estado_Cuenta_{$cliente->identificacion}.pdf");
    }