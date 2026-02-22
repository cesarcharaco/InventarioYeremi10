@extends('layouts.login')

@section('css')
<style>
    /* Forzamos a que el body ocupe todo el alto sin scroll */
    html, body {
        height: 100%;
        margin: 0;
    }

    .login-page {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background-color: #f4f6f9;
        padding: 20px; /* Margen para que no toque los bordes del vidrio */
    }

    .login-box {
        width: 100%;
        max-width: 400px; /* Tamaño máximo en PC */
        margin: 0 auto;
    }

    /* Ajustes específicos para Celulares */
    @media (max-width: 576px) {
        .login-box {
            max-width: 100%; /* Que use todo el ancho disponible */
        }
        
        .login-logo b {
            font-size: 2.5rem; /* Título más grande y visible */
        }

        .card-body {
            padding: 30px 20px !important; /* Más espacio interno para los dedos */
        }

        .btn-block {
            height: 50px; /* Botón más alto para que sea fácil de presionar */
            font-size: 1.2rem;
        }
        
        .form-control {
            height: 45px; /* Inputs más cómodos */
        }
    }
</style>
@endsection

@section('content')
<div class="login-page">
    <div class="login-box">
        <div class="login-logo text-center mb-4">
            <a href="{{ route('login') }}" style="color: #495057; text-decoration: none;">
                <b>SAYER!</b>
            </a>
        </div>
        
        <div class="card shadow-lg border-0">
            <div class="card-body login-card-body">
                @if (session('error') || session('warning'))
                    <div class="alert alert-danger py-2" style="font-size: 0.9rem;">
                        {{ session('error') ?? session('warning') }}
                    </div>
                @endif

                <p class="login-box-msg text-center text-muted mb-4">Inicia sesión para comenzar</p>
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                               placeholder="Correo electrónico" value="{{ old('email') }}" required autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-group mb-4">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                               placeholder="Contraseña" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        <input type="hidden" name="debug_session" value="1">
                    </div>
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember" class="ml-2">Recordarme</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block shadow-sm">
                                <b>Entrar</b> <i class="fas fa-sign-in-alt ml-1"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection