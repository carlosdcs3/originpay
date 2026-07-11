@extends('frontend.layouts.auth')

@section('title', 'Redefinir senha merchant')

@section('auth-content')
    <div class="auth-page d-flex align-items-center justify-content-center">
        <div class="login-card rounded shadow p-4">
            <div class="text-center mb-4">
                <img src="{{ asset(setting('logo')) }}" alt="Logo" class="img-fluid mb-2 login-logo">
                <h5 class="text-success mb-2">Nova senha da conta merchant</h5>
                <p class="text-muted mb-0">
                    Defina uma nova senha para voltar ao painel com seguranca.
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

            <form action="{{ route('merchant.password.store') }}" method="POST">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

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
                            value="{{ old('email', $request->email) }}"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Nova senha</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Digite a nova senha"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label fw-semibold">Confirmar senha</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-key"></i>
                        </span>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="Repita a nova senha"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="fa-light fa-key me-1"></i>
                    Redefinir senha
                </button>
            </form>

            <p class="text-center mt-4 mb-0">
                Lembrou a senha?
                <a href="{{ route('merchant.login') }}" class="text-decoration-none text-success fw-semibold">
                    Voltar para o login
                </a>
            </p>
        </div>
    </div>
@endsection
