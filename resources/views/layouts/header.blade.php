<!-- AdminLTE Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <!-- Sidebar toggle button (AdminLTE) -->
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Hide Sidebar">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Navbar Right Menu -->
    <ul class="navbar-nav ml-auto">
        @auth
            {{-- Esto solo se ejecuta si hay un usuario logueado --}}
            <li class="dropdown">
                <a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    
                    {{-- Foto circular con validación de existencia --}}
                    <img src="{{ auth()->user()->foto ? asset('fotosperfil/'.auth()->user()->foto) : asset('fotosperfil/user-default.png') }}" 
                         style="width: 30px; height: 30px; object-fit: cover; border-radius: 50%; border: 1px solid #fff;">
                    
                    <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                    <i class="fa fa-angle-down"></i>
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
            {{-- Esto es lo que verá el Cliente Mayorista que se está registrando --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ route('login') }}">
                    <i class="fas fa-sign-in-alt"></i> Volver al Login
                </a>
            </li>
        @endguest
    </ul>
</nav>
