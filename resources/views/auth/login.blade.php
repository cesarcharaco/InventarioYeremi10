@extends('layouts.login')

@section('css')
<style>
    /* Estilos para que el login sea realmente responsivo */
    .login-page {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: #e9ecef; /* Color de fondo por si el layout no lo trae */
        padding: 15px; /* Evita que el cuadro pegue a los bordes en el celular */
    }

    .login-box {
        width: 100%;
        max-width: 400px; /* Ancho máximo en PC */
        margin: 0 auto;
    }

    .login-logo b {
        font-size: 2.5rem;
    }

    @media (max-width: 576px) {
        .login-box {
            max-width: 100%; /* Ocupa todo el ancho en móviles pequeños */
        }
        
        .login-card-body {
            padding: 25px 15px !important;
        }

        /* Hace que el botón "Entrar" sea más grande y fácil de presionar */
        .btn-block {
            padding: 12px;
            font-size: 1.1rem;
        }
    }
</style>
@endsection

@section('content')
<div class="login-page">
    <div class="login-box">
        <div class="login-logo text-center mb-3">
            <a href="{{ route('login') }}"><b>SAYER!</b></a>
        </div>
        
        <div class="card shadow-lg"> {{-- Sombra para que resalte --}}
            <div class="card-body login-card-body">
                @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif
                
                @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                @endif

                <p class="login-box-msg text-center">Inicia sesión para comenzar</p>
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    {{-- Email --}}
                    <div class="input-group mb-4">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               name="email" value="{{ old('email') }}" placeholder="Correo electrónico" required autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    {{-- Password --}}
                    <div class="input-group mb-4">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               name="password" placeholder="Contraseña" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <input type="hidden" name="debug_session" value="1">
                    </div>
                    
                    <div class="row align-items-center">
                        <div class="col-7">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember" style="cursor: pointer;">
                                    Recordarme
                                </label>
                            </div>
                        </div>
                        <div class="col-5">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                                Entrar <i class="fas fa-sign-in-alt ml-1"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection