<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        @auth
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                    <i class="far fa-bell" style="font-size: 1.2rem;"></i>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="badge badge-danger navbar-badge" style="font-size: 0.6rem; top: 5px;">
                            {{ auth()->user()->unreadNotifications->count() }}
                        </span>
                    @endif
                </a>
                
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">
                        {{ auth()->user()->unreadNotifications->count() }} Notificaciones Pendientes
                    </span>
                    
                    <div class="dropdown-divider"></div>

                    @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                        <a href="{{ route('notifications.read', $notification->id) }}" class="dropdown-item">
                            <i class="{{ $notification->data['icono'] ?? 'fas fa-envelope' }} mr-2"></i> 
                            <span class="text-sm font-weight-bold">{{ \Str::limit($notification->data['titulo'], 20) }}</span>
                            <span class="float-right text-muted text-xs">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                            <div class="text-xs text-secondary text-truncate">{{ $notification->data['mensaje'] }}</div>
                        </a>
                        <div class="dropdown-divider"></div>
                    @empty
                        <a href="#" class="dropdown-item text-center text-muted">No tienes notificaciones nuevas</a>
                        <div class="dropdown-divider"></div>
                    @endforelse

                    <a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">Ver todas</a>

                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <form action="{{ route('notifications.markAllRead') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item dropdown-footer text-primary" style="border-top: 1px solid #eee;">
                                <i class="fas fa-check-double mr-2"></i> Marcar todas como leídas
                            </button>
                        </form>
                    @endif
                </div>
            </li>

            <li class="dropdown">
                <a class="app-nav__item" href="#" data-toggle="dropdown" style="display: flex; align-items: center; gap: 10px; text-decoration: none; padding: 8px 15px;">
                    <img src="{{ auth()->user()->foto ? asset('fotosperfil/'.auth()->user()->foto) : asset('fotosperfil/user-default.png') }}" 
                         style="width: 30px; height: 30px; object-fit: cover; border-radius: 50%; border: 1px solid #ddd;">
                    <span class="d-none d-md-inline text-dark">{{ auth()->user()->name }}</span>
                    <i class="fa fa-angle-down text-secondary"></i>
                </a>
                <ul class="dropdown-menu settings-menu dropdown-menu-right">
                    <li><a class="dropdown-item" href="{{ route('perfil.edit') }}"><i class="fa fa-user fa-lg"></i> Perfil</a></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('logout') }}" 
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i> {{ __('Cerrar sesión') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </li>
        @endauth

        @guest
            <li class="nav-item">
                <a class="nav-link" href="{{ route('login') }}">
                    <i class="fas fa-sign-in-alt"></i> Volver al Login
                </a>
            </li>
        @endguest
    </ul>
</nav>