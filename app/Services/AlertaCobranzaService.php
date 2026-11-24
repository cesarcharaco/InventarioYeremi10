<?php

namespace App\Services;

use App\Models\Credito;
use App\Models\User;
use App\Notifications\AlertaSistemaNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AlertaCobranzaService
{
    public function procesarAlertas(): array
    {
        $hoy = Carbon::today();
        $alertasEnviadas = 0;

        $creditosPendientes = Credito::query()
            ->with(['cliente', 'venta.local'])
            ->where('estado', 'pendiente')
            ->where('saldo_pendiente', '>', 0)
            ->whereHas('cliente')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $porCliente = $creditosPendientes->groupBy('id_cliente');

        // Cargamos usuarios destinatarios una sola vez por ejecución
        $usuarios = User::query()->get();

        foreach ($porCliente as $clienteId => $creditosCliente) {
            $creditoMasAntiguo = $creditosCliente->first();
            $fechaMasAntigua = Carbon::parse($creditoMasAntiguo->created_at)->startOfDay();
            $diasAntiguedad = $fechaMasAntigua->diffInDays($hoy);

            $tramo = $this->resolverTramo($diasAntiguedad);
            if (!$tramo) {
                continue;
            }

            if ($this->alertaRecienteExiste((int) $clienteId, $tramo, 5)) {
                continue;
            }

            $cliente = $creditoMasAntiguo->cliente;
            $saldoTotal = round((float) $creditosCliente->sum('saldo_pendiente'), 2);
            $idLocal = optional($creditoMasAntiguo->venta)->id_local;
            $nombreLocal = optional(optional($creditoMasAntiguo->venta)->local)->nombre ?? 'Local';

            $destinatarios = $this->resolverDestinatarios($usuarios, $idLocal ? (int) $idLocal : null);
            if ($destinatarios->isEmpty()) {
                continue;
            }

            [$titulo, $icono] = $this->estiloPorTramo($tramo);

            $terminoBusqueda = trim(strip_tags((string) ($cliente->identificacion ?: $cliente->nombre)));

            $queryParams = [];
            if ($idLocal) {
                $queryParams['id_local'] = (int) $idLocal;
            }
            if ($terminoBusqueda !== '') {
                $queryParams['buscar'] = $terminoBusqueda;
            }

            $detalles = [
                'titulo'         => $titulo,
                'mensaje'        => "Cliente {$cliente->nombre} acumula \${$saldoTotal} pendientes (crédito más antiguo desde {$fechaMasAntigua->format('d/m/Y')}, {$diasAntiguedad} días) en {$nombreLocal}.",
                'url'            => route('creditos.cobranza_alertas', $queryParams),
                'icono'          => $icono,
                'cliente_id'     => (int) $clienteId,
                'credito_ref_id' => (int) $creditoMasAntiguo->id,
                'tramo'          => $tramo,
                'id_local'       => $idLocal ? (int) $idLocal : null,
            ];

            foreach ($destinatarios as $usuario) {
                $usuario->notify(new AlertaSistemaNotification($detalles));
            }

            $alertasEnviadas++;
        }

        return [
            'alertas_enviadas' => $alertasEnviadas,
            'fecha'            => $hoy->toDateString(),
        ];
    }

    private function resolverTramo(int $dias): ?string
    {
        if ($dias >= 30) {
            return '30';
        }
        if ($dias >= 20) {
            return '20';
        }
        if ($dias >= 15) {
            return '15';
        }
        return null;
    }

    private function estiloPorTramo(string $tramo): array
    {
        return match ($tramo) {
            '30'    => ['🚨 Crédito con 30+ Días Pendientes', 'fas fa-exclamation-circle text-danger'],
            '20'    => ['⚠️ Crédito con 20+ Días Pendientes', 'fas fa-exclamation-triangle text-warning'],
            default => ['🔔 Crédito con 15+ Días Pendientes', 'fas fa-bell text-warning'],
        };
    }

    private function alertaRecienteExiste(int $clienteId, string $tramo, int $diasMinimos): bool
    {
        $limiteFecha = Carbon::now()->subDays($diasMinimos);

        $ultima = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('created_at', '>=', $limiteFecha)
            ->orderBy('created_at', 'desc')
            ->get()
            ->first(function ($row) use ($clienteId, $tramo) {
                $data = json_decode($row->data ?? '{}', true);
                return isset($data['cliente_id'], $data['tramo'])
                    && (int) $data['cliente_id'] === $clienteId
                    && (string) $data['tramo'] === $tramo;
            });

        return $ultima !== null;
    }

    private function resolverDestinatarios($usuarios, ?int $idLocal)
    {
        // Obtenemos asignaciones activas de locales por usuario
        $localesPorUsuario = DB::table('users_has_local')
            ->where('status', 'activo')
            ->get()
            ->groupBy('id_user')
            ->map(fn ($rows) => $rows->pluck('id_local')->map(fn ($id) => (int) $id)->toArray());

        return $usuarios->filter(function (User $user) use ($idLocal, $localesPorUsuario) {
            $role = strtolower((string) $user->role);

            if ($user->esAdmin() || in_array($role, ['admin', 'superadmin', strtolower(User::ROLE_SUPERADMIN)], true)) {
                return true;
            }

            if (in_array($role, ['encargado', 'vendedor', strtolower(User::ROLE_ENCARGADO), strtolower(User::ROLE_VENDEDOR)], true)) {
                if (!$idLocal) {
                    return false;
                }
                $asignados = $localesPorUsuario[$user->id] ?? [];
                return in_array($idLocal, $asignados, true);
            }

            return false;
        });
    }
}   