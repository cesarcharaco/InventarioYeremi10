
@foreach($insumos as $key)
    @php
<td class="text-center">
    @php
        // 1. Identificar Rol y Alcance
        $rol = is_object(auth()->user()->role) ? auth()->user()->role->nombre : auth()->user()->role;
        $esDuenioLocal = (Gate::allows('gestionar-estado-local', $key->id_local));
        $esAdmin = (in_array($rol, ['Admin', 'Almacenista']));
        
        // 2. Determinar Nivel de Interacción
        $modo = ($esAdmin || ($rol === 'Encargado' && $esDuenioLocal)) ? 'editable' : 'lectura';
    @endphp

    <div class="dropdown">
        @if($modo === 'editable')
            <button class="btn btn-sm btn-link dropdown-toggle p-0" type="button" data-toggle="dropdown" style="text-decoration: none;">
        @endif

        {{-- SWITCH DE VISUALIZACIÓN DE BADGES --}}
        @switch(true)
            {{-- Prioridad 1: Bloqueo Global --}}
            @case($key->estado !== 'En Venta')
                <span class="badge badge-dark" title="Bloqueo desde Administración Central">
                    <i class="fas fa-globe"></i> Global: {{ $key->estado }}
                </span>
                @break

            {{-- Prioridad 2: Bloqueo Local --}}
            @case($key->estado_local === 'Suspendido')
                <span class="badge badge-danger" title="Suspendido en este local">
                    <i class="fas fa-hand-paper"></i> Local: Suspendido
                </span>
                @break

            {{-- Caso 3: Todo Operativo --}}
            @default
                <span class="badge badge-success">
                    <i class="fas fa-check-circle"></i> Disponible
                </span>
        @endswitch

        @if($modo === 'editable')
            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <h6 class="dropdown-header">Gestión de Estado</h6>
                
                {{-- Opciones que verá el usuario --}}
                <a class="dropdown-item" href="#" onclick="updateInsumoEstado({{ $key->id }}, 'En Venta', {{ $key->id_local }}, {{ $esAdmin ? 'true' : 'false' }}, {{ $esDuenioLocal ? 'true' : 'false' }})">
                    <span class="text-success"><i class="fa fa-play"></i> Activar (Venta/Disponible)</span>
                </a>
                
                <a class="dropdown-item" href="#" onclick="updateInsumoEstado({{ $key->id }}, 'Suspendido', {{ $key->id_local }}, {{ $esAdmin ? 'true' : 'false' }}, {{ $esDuenioLocal ? 'true' : 'false' }})">
                    <span class="text-danger"><i class="fa fa-pause"></i> Suspender</span>
                </a>

                @if($esAdmin)
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="updateInsumoEstado({{ $key->id }}, 'No Disponible', {{ $key->id_local }}, true, true)">
                        <span class="text-dark"><i class="fa fa-ban"></i> Marcar No Disponible (Global)</span>
                    </a>
                @endif
            </div>
        @endif
    </div>
</td>