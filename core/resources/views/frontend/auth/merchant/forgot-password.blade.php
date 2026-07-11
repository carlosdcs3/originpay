@extends('frontend.layouts.auth')

@section('title', 'Recuperar senha merchant')

@section('auth-content')
    <div class="auth-page d-flex align-items-center justify-content-center">
        <div class="login-card rounded shadow p-4">
            <div class="text-center mb-4">
                <img src="{{ asset(setting('logo')) }}" alt="Logo" class="img-fluid mb-2 login-logo">
                <h5 class="text-success mb-2">Recuperar acesso merchant</h5>
                <p class="text-muted mb-0">
                    Informe o e-mail vinculado a sua conta merchant para receber o link de redefinicao de senha.
                </p>
                <div class="mt-2 mb-3">
                    <span class="badge bg-success p-2">
                        <i class="fa-duotone fa-store me-1"></i> Conta merchant
                    </span>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    @foreach ($errors->all() as $error)
                        <strong>{{ $error }}</strong><br>
                    @endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            @endif

            <form action="{{ route('merchant.password.email') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">E-mail</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="Digite o e-mail do negocio"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="fa-light fa-paper-plane me-1"></i>
                    Enviar link de redefinicao
                </button>
            </form>

            <p class="text-center mt-4 mb-0">
                Lembrou a senha?
                <a href="{{ route('merchant.login') }}" class="text-decoration-none text-success fw-semibold">
                    Voltar para o login
                </a>
            </p>

            <div class="border-top mt-4 pt-3">
                <p class="text-center mb-0 sm-mb-text">
                    Precisa recuperar uma conta de usuario comum?
                    <a href="{{ route('user.password.request') }}" class="text-decoration-none text-primary fw-semibold">
                        <i class="fa-duotone fa-user me-1"></i> Recuperar conta de usuario
                    </a>
                </p>
            </div>
        </div>
    </div>
@endsection
