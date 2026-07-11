@extends('frontend.user.developer.index')
@section('title', __('Webhooks'))

@section('user_developer_content')

<div class="v2-page-header" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <h2 class="v2-page-title" style="font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; color: var(--ds-text-main);">Webhooks</h2>
        <p class="v2-page-subtitle" style="font-size: 0.875rem; color: var(--ds-text-muted); margin: 0;">Configure endpoints para receber notificações em tempo real sobre eventos na sua conta.</p>
    </div>
    @if($endpoints->count() > 0)
        <button type="button" class="v2-btn-primary" data-bs-toggle="modal" data-bs-target="#createWebhookModal" aria-label="Adicionar endpoint de webhook">
            <i class="fas fa-plus" style="margin-right: 8px;"></i> Adicionar Endpoint
        </button>
    @endif
</div>

@if(session('success'))
<div class="v2-settings-card" role="status" style="padding: 16px 24px; margin-bottom: 24px; border: 1px solid rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.05); display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle text-success" style="font-size: 1.25rem;" aria-hidden="true"></i>
        <span style="font-weight: 500; color: var(--ds-text-main);">{{ session('success') }}</span>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Fechar alerta" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
</div>
@endif

@forelse($endpoints as $endpoint)
<div class="v2-settings-card" style="padding: 24px; margin-bottom: 24px; display: flex; flex-direction: column; transition: transform 200ms ease, box-shadow 200ms ease; border-radius: 16px;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
        <div style="flex: 1; min-width: 0;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 8px;">
                @if($endpoint->environment === 'live')
                    <span class="v2-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);">Live</span>
                @else
                    <span class="v2-badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2);">Test</span>
                @endif

                @if($endpoint->status)
                    <span class="v2-badge v2-badge-success">Ativo</span>
                @else
                    <span class="v2-badge v2-badge-error">Desativado</span>
                @endif

                <span class="v2-badge v2-badge-default">{{ count((array) $endpoint->events) }} eventos</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 6px 12px; display: inline-flex; max-width: 100%;">
                <i class="fas fa-link" style="color: var(--ds-text-muted); font-size: 0.75rem;" aria-hidden="true"></i>
                <span style="font-family: monospace; font-size: 0.875rem; color: var(--ds-text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $endpoint->url }}</span>
            </div>
        </div>

        <div style="display: flex; gap: 8px; margin-left: 16px;">
            <a href="{{ route('user.developer.webhooks.show', $endpoint->id) }}" class="v2-btn-secondary" style="padding: 6px 12px; height: 32px; font-size: 0.8125rem; text-decoration: none;" aria-label="Editar webhook {{ $endpoint->id }}">
                <i class="fas fa-edit" style="margin-right: 6px;" aria-hidden="true"></i> Editar
            </a>
            <a href="{{ route('user.developer.webhooks.show', $endpoint->id) }}" class="v2-btn-tertiary" style="padding: 6px 12px; height: 32px; font-size: 0.8125rem; text-decoration: none;" aria-label="Testar webhook {{ $endpoint->id }}">
                Testar
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
        @php $lastDeliv = optional($endpoint->deliveries)->sortByDesc('created_at')->first(); @endphp
        <div>
            <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-bottom: 4px;">Data de criação</div>
            <div style="font-size: 0.875rem; color: var(--ds-text-main);">{{ $endpoint->created_at->format('d/m/Y') }}</div>
        </div>
        <div>
            <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-bottom: 4px;">Última entrega</div>
            <div style="font-size: 0.875rem; color: var(--ds-text-main);">{{ $lastDeliv ? $lastDeliv->created_at->diffForHumans() : 'Nunca' }}</div>
        </div>
        <div>
            <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-bottom: 4px;">Último HTTP Status</div>
            <div style="font-size: 0.875rem; color: var(--ds-text-main);">
                @if($lastDeliv)
                    @if($lastDeliv->http_status >= 200 && $lastDeliv->http_status < 300)
                        <span class="text-success fw-medium">{{ $lastDeliv->http_status }} OK</span>
                    @else
                        <span class="text-danger fw-medium">{{ $lastDeliv->http_status }} Error</span>
                    @endif
                @else
                    N/A
                @endif
            </div>
        </div>
    </div>
</div>
@empty
<div class="v2-empty-state" style="padding: 28px 22px; text-align: center; border: 1px dashed rgba(255,255,255,0.1); border-radius: 16px; margin-bottom: 24px;">
    <div style="width: 40px; height: 40px; background: rgba(124,58,237,.12); border-radius: 12px; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-size: 1.125rem; margin: 0 auto 12px;">
        <i class="fas fa-satellite-dish" aria-hidden="true"></i>
    </div>
    <h3 style="margin: 0 0 8px; font-size: 1rem; font-weight: 600; color: var(--ds-text-main);">Nenhum webhook configurado</h3>
    <p style="margin: 0 0 16px; color: var(--ds-text-muted); font-size: 0.875rem; line-height: 1.45; max-width: 280px; margin-left: auto; margin-right: auto;">Adicione um endpoint HTTPS para receber eventos em tempo real.</p>
    <button type="button" class="v2-btn-primary" data-bs-toggle="modal" data-bs-target="#createWebhookModal" aria-label="Adicionar endpoint de webhook" style="padding: 0 14px; height: 36px; font-size: 0.82rem; min-width: 0;">
        <i class="fas fa-plus" aria-hidden="true"></i>
        Adicionar endpoint
    </button>
</div>
@endforelse

<div class="modal fade" id="createWebhookModal" tabindex="-1" aria-labelledby="createWebhookModalTitle" inert>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('user.developer.webhooks.store') }}" method="POST" class="modal-content" style="border-radius: 16px; border: 1px solid var(--ds-border-light); background: var(--ds-bg-card);">
            @csrf
            <div class="modal-header" style="border-bottom: 1px solid var(--ds-border-light); padding: 24px;">
                <h5 class="modal-title fw-bold" id="createWebhookModalTitle" style="color: var(--ds-text-main); font-size: 1.125rem;">Adicionar Endpoint de Webhook</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div style="margin-bottom: 24px;">
                    <label for="url" class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 8px;">Endpoint URL</label>
                    <input type="url" class="v2-input" id="url" name="url" placeholder="https://sua-api.com.br/webhooks/originpay" autocomplete="url" required style="padding: 0 16px; height: 44px; border-radius: 12px; border: 1px solid var(--ds-border-medium); background: rgba(255,255,255,0.03); color: white;">
                    <div class="form-text" style="color: var(--ds-text-muted); font-size: 0.75rem; margin-top: 8px;">A URL deve ser HTTPS e estar acessível publicamente.</div>
                </div>

                <fieldset style="margin-bottom: 24px; border: 0; padding: 0;">
                    <legend class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 12px;">Ambiente</legend>
                    <div style="display: flex; gap: 16px;">
                        <label for="webhook_environment_test" class="d-flex align-items-center" style="border: 1px solid var(--ds-border-medium); border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; background: rgba(255,255,255,0.02); flex: 1;">
                            <input type="radio" id="webhook_environment_test" name="environment" value="test" class="form-check-input me-3" checked style="width: 18px; height: 18px; margin-top: 0;">
                            <span style="font-weight: 600; color: var(--ds-text-main); font-size: 0.875rem;">Sandbox (Testes)</span>
                        </label>

                        <label for="webhook_environment_live" class="d-flex align-items-center" style="border: 1px solid var(--ds-border-medium); border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; background: rgba(255,255,255,0.02); flex: 1;">
                            <input type="radio" id="webhook_environment_live" name="environment" value="live" class="form-check-input me-3" style="width: 18px; height: 18px; margin-top: 0;">
                            <span style="font-weight: 600; color: var(--ds-text-main); font-size: 0.875rem;">Produção (Live)</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset style="margin-bottom: 12px; border: 0; padding: 0;">
                    <legend class="v2-label" style="color: var(--ds-text-main); font-size: 0.875rem; margin-bottom: 12px;">Eventos para escutar</legend>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check p-3 rounded" style="border: 1px solid var(--ds-border-light); background: rgba(255,255,255,0.02);">
                                <input class="form-check-input ms-1 me-2" type="checkbox" name="events[]" value="charge.created" id="ev_charge_created" checked>
                                <label class="form-check-label d-inline-block mt-1" for="ev_charge_created" style="color: var(--ds-text-main); font-weight: 500; font-size: 0.875rem;">
                                    charge.created
                                    <span class="text-muted d-block" style="font-size: 0.75rem; font-weight: normal; margin-top: 4px;">Disparado ao criar uma cobrança</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check p-3 rounded" style="border: 1px solid var(--ds-border-light); background: rgba(255,255,255,0.02);">
                                <input class="form-check-input ms-1 me-2" type="checkbox" name="events[]" value="charge.paid" id="ev_charge_paid" checked>
                                <label class="form-check-label d-inline-block mt-1" for="ev_charge_paid" style="color: var(--ds-text-main); font-weight: 500; font-size: 0.875rem;">
                                    charge.paid
                                    <span class="text-muted d-block" style="font-size: 0.75rem; font-weight: normal; margin-top: 4px;">Disparado ao confirmar o pagamento</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check p-3 rounded" style="border: 1px solid var(--ds-border-light); background: rgba(255,255,255,0.02);">
                                <input class="form-check-input ms-1 me-2" type="checkbox" name="events[]" value="charge.refunded" id="ev_charge_refunded">
                                <label class="form-check-label d-inline-block mt-1" for="ev_charge_refunded" style="color: var(--ds-text-main); font-weight: 500; font-size: 0.875rem;">
                                    charge.refunded
                                    <span class="text-muted d-block" style="font-size: 0.75rem; font-weight: normal; margin-top: 4px;">Disparado no reembolso de cobrança</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check p-3 rounded" style="border: 1px solid var(--ds-border-light); background: rgba(255,255,255,0.02);">
                                <input class="form-check-input ms-1 me-2" type="checkbox" name="events[]" value="transfer.created" id="ev_transfer_created">
                                <label class="form-check-label d-inline-block mt-1" for="ev_transfer_created" style="color: var(--ds-text-main); font-weight: 500; font-size: 0.875rem;">
                                    transfer.created
                                    <span class="text-muted d-block" style="font-size: 0.75rem; font-weight: normal; margin-top: 4px;">Disparado em saques e repasses</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--ds-border-light); padding: 24px; gap: 12px;">
                <button type="button" class="v2-btn-tertiary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="v2-btn-primary" data-tp-confirm="true">Adicionar Endpoint</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('createWebhookModal');
        if (!modal) return;

        modal.addEventListener('show.bs.modal', function () {
            modal.removeAttribute('inert');
        });

        modal.addEventListener('hidden.bs.modal', function () {
            modal.setAttribute('inert', '');
        });
    });
</script>

@endsection


