<div class="row mb-4">
    <div class="col-md-12">
        <div class="form-group">
            <label class="form-label fw-bold text-danger">Motivo da Alteração *</label>
            <textarea class="form-control border-danger" name="reason" rows="2" placeholder="Ex: Correção da estratégia de precificação. Adequação contratual." required></textarea>
            <small class="text-muted">Isso será gravado no log de auditoria de forma imutável.</small>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="form-group">
            <label class="form-label fw-bold">Vigência (Aplicar)</label>
            <div>
                <label class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="apply_type" value="immediate" checked onchange="toggleApplyDate(this)">
                    <span class="form-check-label">Imediatamente</span>
                </label>
                <label class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="apply_type" value="future" onchange="toggleApplyDate(this)">
                    <span class="form-check-label">Em data futura</span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 future-date-section" style="display: none;">
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label fw-bold">Data e Hora de Aplicação</label>
            <input type="datetime-local" class="form-control future-date-input" name="applied_at">
        </div>
    </div>
</div>
