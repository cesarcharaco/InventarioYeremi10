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
        <!-- TU CÓDIGO DEL USER MENU (solo clases cambiadas) -->
        <li class="nav-item dropdown">
            <a class="nav-link" href="#" data-toggle="dropdown" aria-label="Open Profile Menu">
                <i class="fas fa-user"></i> {{ Auth::check() ? Auth::user()->name : 'Sesión expirada' }}
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <li>
                    <a class="dropdown-item" href="{{ route('logout') }}" 
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i> {{ __('Cerrar sesión') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </div>
        </li>
    </ul>
</nav>
