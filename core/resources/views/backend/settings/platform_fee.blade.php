@extends('backend.settings.index')
@section('setting_title', $pageTitle)
@section('setting_content')
@include('backend.finance.partials._tariffs_tabs')

    <div class="row">
        <div class="col-lg-12">
            
            {{-- PIX CARD --}}
            <div class="card mb-4">
                <div class="card-header border-bottom" style="background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i> Taxas PIX</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.platform_fee.update_pix') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Modo de Cobrança</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pix_mode" id="pixModeUniversal" value="universal" {{ ($payload['pix']['mode'] ?? 'universal') === 'universal' ? 'checked' : '' }} onchange="togglePixMode()">
                                    <label class="form-check-label" for="pixModeUniversal">Valor único para qualquer transação</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pix_mode" id="pixModeRange" value="range" {{ ($payload['pix']['mode'] ?? 'universal') === 'range' ? 'checked' : '' }} onchange="togglePixMode()">
                                    <label class="form-check-label" for="pixModeRange">Política por faixa</label>
                                </div>
                            </div>
                        </div>

                        <div id="pixUniversalSection" style="display: {{ ($payload['pix']['mode'] ?? 'universal') === 'universal' ? 'block' : 'none' }};">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Valor Universal (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" class="form-control" name="universal_fee" value="{{ number_format($payload['pix']['universal_fee'] ?? 0, 2, '.', '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="pixRangeSection" style="display: {{ ($payload['pix']['mode'] ?? 'universal') === 'range' ? 'block' : 'none' }};">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Limite da Faixa (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" class="form-control" name="range_limit" value="{{ number_format($payload['pix']['range_limit'] ?? 0, 2, '.', '') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Taxa fixa até o limite (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" class="form-control" name="range_fixed_fee" value="{{ number_format($payload['pix']['range_fixed_fee'] ?? 0, 2, '.', '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Taxa percentual acima do limite (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" name="range_percentage_fee" value="{{ number_format($payload['pix']['range_percentage_fee'] ?? 0, 2, '.', '') }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Taxa fixa adicional acima (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" class="form-control" name="range_additional_fixed_fee" value="{{ number_format($payload['pix']['range_additional_fixed_fee'] ?? 0, 2, '.', '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        @include('backend.settings.partials._platform_fee_shared')
                        
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Salvar PIX</button>
                    </form>
                </div>
            </div>

            {{-- BOLETO CARD --}}
            <div class="card mb-4">
                <div class="card-header border-bottom" style="background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                    <h5 class="mb-0"><i class="fas fa-barcode me-2"></i> Taxas Boleto</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.platform_fee.update_boleto') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Taxa fixa por boleto (R$)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control" name="fixed_fee" value="{{ number_format($payload['boleto']['fixed_fee'] ?? 0, 2, '.', '') }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Taxa percentual (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="percentage_fee" value="{{ number_format($payload['boleto']['percentage_fee'] ?? 0, 2, '.', '') }}" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Valor mínimo (R$)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control" name="min_value" value="{{ number_format($payload['boleto']['min_value'] ?? 0, 2, '.', '') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        @include('backend.settings.partials._platform_fee_shared')

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Salvar Boleto</button>
                    </form>
                </div>
            </div>

            {{-- CREDIT CARD CARD --}}
            <div class="card mb-4">
                <div class="card-header border-bottom" style="background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i> Taxas Cartão</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.platform_fee.update_card') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Taxa percentual (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" name="percentage_fee" value="{{ number_format($payload['credit_card']['percentage_fee'] ?? 0, 2, '.', '') }}" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold">Taxa fixa (R$)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control" name="fixed_fee" value="{{ number_format($payload['credit_card']['fixed_fee'] ?? 0, 2, '.', '') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        @include('backend.settings.partials._platform_fee_shared')

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Salvar Cartão</button>
                    </form>
                </div>
            </div>
            
            {{-- AUDIT HISTORY --}}
            <div class="card mt-4">
                <div class="card-header border-bottom" style="background: var(--ds-surface-hover, rgba(0,0,0,0.02));">
                    <h5 class="mb-0">Histórico de Auditoria</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Admin</th>
                                    <th>Motivo</th>
                                    <th>Alteração (Before/After)</th>
                                    <th>Vigência</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($audits as $audit)
                                    <tr>
                                        <td>{{ $audit->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td>{{ $audit->admin ? $audit->admin->name : 'Sistema' }}<br><small class="text-muted">{{ $audit->ip_address }}</small></td>
                                        <td>{{ $audit->reason }}</td>
                                        <td>
                                            @if($audit->changes && isset($audit->changes['type']))
                                                <span class="badge bg-secondary mb-1">Tipo: {{ strtoupper($audit->changes['type']) }}</span>
                                                <div style="font-size: 0.8rem; background: #f8f9fa; padding: 5px; border-radius: 4px;">
                                                    <div class="text-danger">(-) Before: <pre class="m-0" style="font-size: 0.75rem;">{{ json_encode($audit->changes['before'] ?? [], JSON_PRETTY_PRINT) }}</pre></div>
                                                    <div class="text-success mt-1">(+) After: <pre class="m-0" style="font-size: 0.75rem;">{{ json_encode($audit->changes['after'] ?? [], JSON_PRETTY_PRINT) }}</pre></div>
                                                </div>
                                            @else
                                                <span class="badge bg-info">Migração/Inicial</span>
                                            @endif
                                        </td>
                                        <td>{{ $audit->applied_at ? $audit->applied_at->format('d/m/Y H:i:s') : 'Imediato' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Nenhum registro de auditoria encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $audits->links() }}
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        function togglePixMode() {
            var mode = document.querySelector('input[name="pix_mode"]:checked').value;
            if(mode === 'universal') {
                document.getElementById('pixUniversalSection').style.display = 'block';
                document.getElementById('pixRangeSection').style.display = 'none';
            } else {
                document.getElementById('pixUniversalSection').style.display = 'none';
                document.getElementById('pixRangeSection').style.display = 'block';
            }
        }
        
        function toggleApplyDate(elem) {
            var parent = elem.closest('form');
            var type = parent.querySelector('input[name="apply_type"]:checked').value;
            if(type === 'future') {
                parent.querySelector('.future-date-section').style.display = 'block';
                parent.querySelector('.future-date-input').required = true;
            } else {
                parent.querySelector('.future-date-section').style.display = 'none';
                parent.querySelector('.future-date-input').required = false;
            }
        }
    </script>
    @endpush
@endsection
