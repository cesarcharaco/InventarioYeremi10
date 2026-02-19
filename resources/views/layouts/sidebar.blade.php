<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ url('home') }}" class="brand-link">
        <img src="{{ asset('images/logo1Yerem.png') }}" alt="Logo SAY ER" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">SAYER System</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                {{-- INICIO: Visible para todos --}}
                <li class="nav-item">
                    <a href="{{ url('home') }}" class="nav-link {{ Request::is('home') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Inicio</p>
                    </a>
                </li>

                {{-- MÓDULO INVENTARIO: Visible para todos, pero con lógica interna --}}
                <li class="nav-item has-treeview {{ Request::is('insumos*') || Request::is('precios*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('insumos*') || Request::is('precios*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-box"></i>
                        <p>SAYER <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('insumos.index') }}" class="nav-link {{ Request::is('insumos') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Inventario</p>
                            </a>
                        </li>
                        {{-- Solo Admin y Encargado ven Precios de Costo/Gestión --}}
                        @if(auth()->user()->esAdmin() || auth()->user()->hasRole('encargado'))
                        <li class="nav-item">
                            <a href="{{ route('insumos.precios') }}" class="nav-link {{ Request::is('*precios*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Gestión de Precios</p>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                {{-- INCIDENCIAS: Todos registran, pero solo Admin ve el historial completo --}}
                <li class="nav-item has-treeview {{ Request::is('incidencias*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('incidencias*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-exclamation-triangle text-warning"></i>
                        <p>Incidencias <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('incidencias.index') }}" class="nav-link {{ Request::is('incidencias') ? 'active' : '' }}">
                                <i class="fas fa-plus-circle nav-icon"></i>
                                <p>Registrar / Ver</p>
                            </a>
                        </li>
                        @can('anular-historial') {{-- Gate que creamos antes --}}
                        <li class="nav-item">
                            <a href="{{ route('incidencias.historial') }}" class="nav-link {{ Request::is('incidencias/historial*') ? 'active' : '' }}">
                                <i class="fas fa-history nav-icon"></i>
                                <p>Auditoría (Admin)</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- LOGÍSTICA: Principalmente para Almacenistas y Admin --}}
                @if(!auth()->user()->hasRole('vendedor'))
                <li class="nav-item has-treeview {{ Request::is('despacho*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('despacho*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-truck"></i>
                        <p>Logística <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('despacho.create') }}" class="nav-link {{ Request::is('despacho/create') ? 'active' : '' }}">
                                <i class="fas fa-plus-circle nav-icon text-primary"></i>
                                <p>Nuevo Despacho</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('despacho.index') }}" class="nav-link {{ Request::is('despacho') ? 'active' : '' }}">
                                <i class="fas fa-history nav-icon"></i>
                                <p>Historial</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                {{-- NUEVO: MÓDULO CLIENTES (Visible para Admin, Encargado y Vendedor) --}}
                @can('gestionar-clientes')
                <li class="nav-item has-treeview {{ Request::is('clientes*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('clientes*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-address-book"></i>
                        <p>Cartera de Clientes <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('clientes.index') }}" class="nav-link {{ Request::is('clientes') ? 'active' : '' }}">
                                <i class="fas fa-list nav-icon"></i>
                                <p>Listado General</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('clientes.create') }}" class="nav-link {{ Request::is('clientes/create') ? 'active' : '' }}">
                                <i class="fas fa-user-plus nav-icon text-success"></i>
                                <p>Nuevo Cliente</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan
                
                <li class="nav-header">OPERACIONES DE VENTA</li>

                {{-- Solo quienes pueden operar la caja ven este menú --}}
                @can('operar-caja')
                    <li class="nav-item">
                        <a href="{{ route('cajas.create') }}" class="nav-link {{ request()->is('cajas*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cash-register text-info"></i>
                            <p>Caja / Jornada</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('ventas.create') }}" class="nav-link {{ request()->is('ventas/create') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart text-success"></i>
                            <p>Realizar Venta</p>
                        </a>
                    </li>
                @endcan

                {{-- Solo Admin o Encargados ven el historial total o auditan cajas --}}
                @can('auditar-cajas')
                    <li class="nav-item">
                        <a href="{{ route('ventas.index') }}" class="nav-link {{ request()->is('ventas') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-history text-warning"></i>
                            <p>Historial de Ventas</p>
                        </a>
                    </li>
                @endcan
                {{-- REPORTES Y GRÁFICAS: Exclusivo SuperAdmin --}}
                @if(auth()->user()->esAdmin())
                <li class="nav-header">REPORTES GERENCIALES</li>
                <li class="nav-item">
                    <a href="{{ route('reportes.index') }}" class="nav-link {{ Request::is('graficas*') || Request::is('reportes*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>Gráficas de Ganancias</p>
                    </a>
                </li>
                @endif

                {{-- CONFIGURACIONES: Solo Admin y Encargado --}}
                @if(auth()->user()->esAdmin() || auth()->user()->hasRole('encargado'))
                <li class="nav-header">SISTEMA</li>
                <li class="nav-item has-treeview {{ Request::is('categorias*') || Request::is('modelos-venta*') || Request::is('local*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('categorias*') || Request::is('modelos-venta*') || Request::is('local*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Configuraciones <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @if(auth()->user()->esAdmin())
                        <li class="nav-item">
                            <a href="{{ route('local.index') }}" class="nav-link {{ Request::is('local*') ? 'active' : '' }}">
                                <i class="fas fa-store nav-icon"></i>
                                <p>Locales</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('categorias.index') }}" class="nav-link {{ Request::is('categorias*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Categorías</p>
                            </a>
                        </li>
                        @can('gestionar-usuarios')
                        <li class="nav-item">
                            <a href="{{ route('usuarios.index') }}" class="nav-link {{ Request::is('usuarios*') ? 'active' : '' }}">
                                <i class="fas fa-users-cog nav-icon"></i>
                                <p>Usuarios</p>
                            </a>
                        </li>
                        {{-- Modelos de Venta (RESTAURADO) --}}
                        <li class="nav-item">
                            <a href="{{ route('modelos-venta.index') }}" class="nav-link {{ Request::is('modelos-venta*') ? 'active' : '' }}">
                                <i class="fas fa-tags nav-icon"></i>
                                <p>Modelos de Venta</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

            </ul>
        </nav>
    </div>
</aside>