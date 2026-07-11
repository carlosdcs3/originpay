<div class="card border-0 shadow-sm">
    <div class="card-header bg-white pt-4 pb-3 border-bottom-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fa-solid fa-heart-pulse me-2 text-muted"></i> {{ __('Saúde do Gateway') }}</h5>
        <button class="btn btn-sm btn-outline-primary" onclick="alert('Iniciando health check...')">
            <i class="fa-solid fa-stethoscope me-1"></i> {{ __('Rodar Diagnóstico') }}
        </button>
    </div>
    <div class="card-body">
        <div class="text-center py-5">
            <div class="mb-3"><i class="fa-solid fa-notes-medical fa-3x text-muted opacity-50"></i></div>
            <h5 class="text-muted">{{ __('Health check ainda não executado') }}</h5>
            <p class="text-muted small">{{ __('O sistema monitora a latência e erros em tempo real.') }}<br>{{ __('Métricas estarão disponíveis assim que o gateway começar a processar transações.') }}</p>
        </div>
    </div>
</div>
