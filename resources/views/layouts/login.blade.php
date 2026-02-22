<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<title>SAYER! | @yield('title')</title>
    @include('layouts.css')
    @yield('css')
</head>
<body>
    {{-- ✅ MENSAJE DE ERROR DE BASE DE DATOS --}}
    @if (session('db_error'))
    <div class="login-page">
        <div style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
            <div class="alert alert-warning alert-dismissible fade show shadow-lg" role="alert">
                <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                <strong>¡Servidor no disponible!</strong><br>
                {!! session('db_error') !!}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        </div>
    </div>
    @endif
    @yield('content')
    @include('layouts.scripts')
    @yield('scripts')
</body>
</html>
