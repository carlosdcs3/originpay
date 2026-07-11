@extends('frontend.layouts.user-v2')
@section('title', 'Origin Connect - Novo Segmento')

@section('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
@endsection

@section('content')
<div style="display: flex; flex-direction: column; gap: 15px;" x-data="segmentBuilder()">
    
    <!-- Barra de Ações Compacta -->
    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--ds-bg-darker); padding: 12px 20px; border-radius: 8px; border: 1px solid var(--ds-border-light, rgba(255,255,255,0.1));">
        <p style="margin: 0; font-size: 0.8125rem; color: var(--ds-text-muted);">Crie uma audiência dinâmica baseada em regras. Os contatos serão atualizados automaticamente conforme atenderem aos critérios definidos.</p>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('user.connect.segments.index') }}" class="v2-btn-secondary" style="height: 32px; padding: 0 16px; font-size: 0.75rem;">Cancelar</a>
            <button class="v2-btn-primary" style="height: 32px; padding: 0 16px; font-size: 0.75rem; gap: 7px;" @click="saveSegment()">
                <i class="fas fa-save" style="font-size: 0.75rem;"></i> Salvar Segmento
            </button>
        </div>
    </div>

    <!-- Grid Layout 8 / 4 -->
    <div style="display: grid; grid-template-columns: 8fr 4fr; gap: 20px; flex: 1; min-height: 0; align-items: start;">
        
        <!-- MAIN CONTENT (Grid 8) -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Card 1: Informações -->
            <div class="v2-settings-card" style="padding: 20px; display: flex; flex-direction: column; gap: 15px; border: 1px solid var(--ds-border-light, rgba(255,255,255,0.05));">
                <h3 style="font-size: 1rem; font-weight: 600; margin: 0; color: var(--ds-text-main);">Informações</h3>
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted);">Nome <span style="color: #ef4444;">*</span></label>
                    <input type="text" x-model="name" class="form-control" placeholder="Ex: Clientes VIPs" style="font-size: 0.8rem; height: 36px; padding: 4px 10px; background: rgba(255,255,255,0.03);">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted);">Descrição</label>
                    <input type="text" x-model="description" class="form-control" placeholder="Opcional" style="font-size: 0.8rem; height: 36px; padding: 4px 10px; background: rgba(255,255,255,0.03);">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 0.75rem; font-weight: 600; color: var(--ds-text-muted);">Tipo</label>
                    <input type="text" value="Dinâmico" readonly disabled class="form-control" style="font-size: 0.8rem; height: 36px; padding: 4px 10px; background: rgba(0,0,0,0.2); cursor: not-allowed; width: 150px; opacity: 0.7;">
                </div>
            </div>

            <!-- Card 2: Rule Builder -->
            <div class="v2-settings-card" style="padding: 20px; display: flex; flex-direction: column; gap: 15px; border: 1px solid var(--ds-border-light, rgba(255,255,255,0.05));">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 600; margin: 0; color: var(--ds-text-main);">Regras do Segmento</h3>
                    <p style="font-size: 0.8125rem; color: var(--ds-text-muted); margin: 4px 0 0 0;">Os contatos que atenderem estas condições farão parte automaticamente deste segmento.</p>
                </div>
                
                <!-- Linguagem Natural / Descrição Dinâmica -->
                <div style="background: rgba(139, 92, 246, 0.1); border-left: 3px solid var(--ds-primary); padding: 12px; border-radius: 0 6px 6px 0; margin-bottom: 10px;">
                    <p style="font-size: 0.8125rem; color: var(--ds-text-main); margin: 0; line-height: 1.5;" x-html="naturalLanguage()"></p>
                </div>

                <!-- Nested Rule Builder Component -->
                <div class="rule-group" style="background: rgba(0,0,0,0.15); border: 1px solid var(--ds-border-light, rgba(255,255,255,0.1)); border-radius: 8px; padding: 15px;">
                    
                    <template x-if="payload.rules.length === 0">
                        <div style="text-align: center; padding: 20px 0;">
                            <p style="color: var(--ds-text-muted); font-size: 0.875rem; margin-bottom: 15px;">Nenhuma condição adicionada.<br>Comece adicionando a primeira regra do segmento.</p>
                            <button type="button" class="v2-btn-secondary" style="font-size: 0.75rem; padding: 6px 12px; height: auto;" @click="addCondition(payload)">
                                <i class="fas fa-plus"></i> Adicionar condição
                            </button>
                        </div>
                    </template>
                    
                    <template x-if="payload.rules.length > 0">
                        <div>
                            <!-- Header do Grupo Principal -->
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                <select x-model="payload.condition" class="form-control" style="width: auto; height: 32px; font-size: 0.75rem; padding: 4px 30px 4px 10px; background-color: rgba(255,255,255,0.05);">
                                    <option value="AND">TODAS (E)</option>
                                    <option value="OR">QUALQUER (OU)</option>
                                </select>
                                <span style="font-size: 0.75rem; color: var(--ds-text-muted);">das seguintes regras devem ser verdadeiras:</span>
                            </div>
                            
                            <!-- Regras Renderizadas recursivamente -->
                            <div style="display: flex; flex-direction: column; gap: 10px; margin-left: 10px; border-left: 2px solid rgba(255,255,255,0.1); padding-left: 15px;">
                                <template x-for="(rule, index) in payload.rules" :key="index">
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        
                                        <!-- Se for uma condição simples -->
                                        <template x-if="!rule.rules">
                                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                                <select x-model="rule.field" class="form-control" @change="rule.operator = getDefaultOperator(rule.field); rule.value = ''" style="width: 160px; height: 32px; font-size: 0.8rem; padding: 4px 30px 4px 10px; background-color: rgba(255,255,255,0.05);">
                                                    <template x-for="field in availableFields" :key="field.id">
                                                        <option :value="field.id" x-text="field.label"></option>
                                                    </template>
                                                </select>
                                                
                                                <select x-model="rule.operator" class="form-control" style="width: 160px; height: 32px; font-size: 0.8rem; padding: 4px 30px 4px 10px; background-color: rgba(255,255,255,0.05);">
                                                    <template x-for="op in getOperatorsForField(rule.field)" :key="op.id">
                                                        <option :value="op.id" x-text="op.label"></option>
                                                    </template>
                                                </select>
                                                
                                                <template x-if="!['is_null', 'is_not_null'].includes(rule.operator)">
                                                    <input type="text" x-model="rule.value" class="form-control" placeholder="Valor..." style="flex: 1; min-width: 150px; height: 32px; font-size: 0.8rem; padding: 4px 10px; background-color: rgba(255,255,255,0.05);">
                                                </template>
                                                
                                                <button type="button" @click="removeRule(payload, index)" style="background: transparent; border: none; color: var(--ds-text-muted); cursor: pointer; padding: 6px; border-radius: 4px;" onmouseover="this.style.color='#ef4444'; this.style.background='rgba(239, 68, 68, 0.1)';" onmouseout="this.style.color='var(--ds-text-muted)'; this.style.background='transparent';">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </template>
                                        
                                        <!-- Se for um Grupo aninhado -->
                                        <template x-if="rule.rules">
                                            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; padding: 12px; margin-top: 5px;">
                                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; justify-content: space-between;">
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <select x-model="rule.condition" class="form-control" style="width: auto; height: 28px; font-size: 0.7rem; padding: 2px 24px 2px 8px; background-color: rgba(0,0,0,0.2);">
                                                            <option value="AND">TODAS (E)</option>
                                                            <option value="OR">QUALQUER (OU)</option>
                                                        </select>
                                                        <span style="font-size: 0.7rem; color: var(--ds-text-muted);">das sub-regras:</span>
                                                    </div>
                                                    <button type="button" @click="removeRule(payload, index)" style="background: transparent; border: none; color: var(--ds-text-muted); cursor: pointer; font-size: 0.75rem;" onmouseover="this.style.color='#ef4444';" onmouseout="this.style.color='var(--ds-text-muted)';">
                                                        <i class="fas fa-trash-alt"></i> Excluir Grupo
                                                    </button>
                                                </div>
                                                
                                                <div style="display: flex; flex-direction: column; gap: 10px; margin-left: 10px; border-left: 1px dashed rgba(255,255,255,0.15); padding-left: 12px;">
                                                    <template x-for="(subrule, subindex) in rule.rules" :key="subindex">
                                                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                                            <select x-model="subrule.field" class="form-control" @change="subrule.operator = getDefaultOperator(subrule.field); subrule.value = ''" style="width: 140px; height: 30px; font-size: 0.75rem; padding: 2px 24px 2px 8px; background-color: rgba(255,255,255,0.05);">
                                                                <template x-for="field in availableFields" :key="field.id">
                                                                    <option :value="field.id" x-text="field.label"></option>
                                                                </template>
                                                            </select>
                                                            <select x-model="subrule.operator" class="form-control" style="width: 140px; height: 30px; font-size: 0.75rem; padding: 2px 24px 2px 8px; background-color: rgba(255,255,255,0.05);">
                                                                <template x-for="op in getOperatorsForField(subrule.field)" :key="op.id">
                                                                    <option :value="op.id" x-text="op.label"></option>
                                                                </template>
                                                            </select>
                                                            <template x-if="!['is_null', 'is_not_null'].includes(subrule.operator)">
                                                                <input type="text" x-model="subrule.value" class="form-control" placeholder="Valor..." style="flex: 1; min-width: 120px; height: 30px; font-size: 0.75rem; padding: 2px 8px; background-color: rgba(255,255,255,0.05);">
                                                            </template>
                                                            <button type="button" @click="removeRule(rule, subindex)" style="background: transparent; border: none; color: var(--ds-text-muted); cursor: pointer;" onmouseover="this.style.color='#ef4444';" onmouseout="this.style.color='var(--ds-text-muted)';">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </template>
                                                    <div style="display: flex; gap: 10px; margin-top: 5px;">
                                                        <button type="button" class="v2-btn-secondary" style="font-size: 0.7rem; padding: 4px 8px; height: auto;" @click="addCondition(rule)">
                                                            + Condição
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Add buttons for main group -->
                            <div style="display: flex; gap: 10px; margin-top: 15px; margin-left: 25px;">
                                <button type="button" class="v2-btn-secondary" style="font-size: 0.75rem; padding: 6px 12px; height: auto; border: 1px dashed rgba(255,255,255,0.2); background: rgba(255,255,255,0.02);" @click="addCondition(payload)">
                                    <i class="fas fa-plus"></i> Adicionar condição
                                </button>
                                <button type="button" class="v2-btn-secondary" style="font-size: 0.75rem; padding: 6px 12px; height: auto; border: 1px dashed rgba(255,255,255,0.2); background: rgba(255,255,255,0.02);" @click="addGroup(payload)">
                                    <i class="fas fa-layer-group"></i> Adicionar subgrupo
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- SIDEBAR (Grid 4) -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Resumo -->
            <div class="v2-settings-card" style="padding: 20px; border: 1px solid var(--ds-border-light, rgba(255,255,255,0.05));">
                <h3 style="font-size: 1rem; font-weight: 600; margin: 0 0 15px 0; color: var(--ds-text-main);">Resumo</h3>
                
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.8125rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--ds-text-muted);">Nome</span>
                        <span style="color: var(--ds-text-main); font-weight: 500;" x-text="name || '-'"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--ds-text-muted);">Tipo</span>
                        <span style="color: var(--ds-primary); font-weight: 500;">Dinâmico</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--ds-text-muted);">Condições</span>
                        <span style="color: var(--ds-text-main); font-weight: 500;" x-text="countRules(payload)"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--ds-text-muted);">Grupo Principal</span>
                        <span style="color: var(--ds-text-main); font-weight: 500;" x-text="payload.condition === 'AND' ? 'Todas (AND)' : 'Qualquer (OR)'"></span>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="v2-settings-card" style="padding: 20px; display: flex; flex-direction: column; gap: 15px; border: 1px solid var(--ds-border-light, rgba(255,255,255,0.05));">
                <h3 style="font-size: 1rem; font-weight: 600; margin: 0; color: var(--ds-text-main);">Prévia</h3>
                
                <div style="background: rgba(0,0,0,0.2); border-radius: 6px; padding: 15px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-size: 0.75rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 5px;">Contatos encontrados</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--ds-text-main);" x-text="previewStatus === 'loading' ? '...' : previewTotal"></div>
                </div>
                
                <button type="button" class="v2-btn-secondary" style="width: 100%; justify-content: center; gap: 8px;" @click="runPreview()" :disabled="previewStatus === 'loading' || payload.rules.length === 0">
                    <i class="fas fa-sync-alt" :class="{'fa-spin': previewStatus === 'loading'}"></i> Atualizar Prévia
                </button>
                
                <!-- Lista de 10 primeiros -->
                <template x-if="previewContacts.length > 0">
                    <div style="margin-top: 5px;">
                        <div style="font-size: 0.75rem; color: var(--ds-text-muted); margin-bottom: 10px;">Exibindo os primeiros <span x-text="previewContacts.length"></span> contatos:</div>
                        <div style="display: flex; flex-direction: column; gap: 8px; max-height: 250px; overflow-y: auto; padding-right: 5px;">
                            <template x-for="contact in previewContacts" :key="contact.id">
                                <div style="display: flex; flex-direction: column; background: rgba(255,255,255,0.03); padding: 8px 10px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.05);">
                                    <span style="font-size: 0.8125rem; color: var(--ds-text-main); font-weight: 500;" x-text="contact.name || 'Sem nome'"></span>
                                    <span style="font-size: 0.7rem; color: var(--ds-text-muted);" x-text="contact.email || contact.whatsapp || '-'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                
                <template x-if="previewStatus === 'error'">
                    <div style="color: #ef4444; font-size: 0.75rem; text-align: center; margin-top: 5px;">
                        Falha ao calcular prévia. Verifique as regras.
                    </div>
                </template>
            </div>
            
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('segmentBuilder', () => ({
        name: '',
        description: '',
        payload: {
            condition: 'AND',
            rules: []
        },
        
        previewTotal: 0,
        previewContacts: [],
        previewStatus: 'idle',
        
        availableFields: [
            { id: 'name', label: 'Nome' },
            { id: 'email', label: 'Email' },
            { id: 'whatsapp', label: 'WhatsApp' },
            { id: 'tag', label: 'Tag' },
            { id: 'source', label: 'Origem' },
            { id: 'status', label: 'Status' },
            { id: 'created_at', label: 'Criado em' }
        ],
        
        operators: [
            { id: 'equals', label: 'É igual a' },
            { id: 'not_equals', label: 'Não é igual a' },
            { id: 'contains', label: 'Contém' },
            { id: 'not_contains', label: 'Não contém' },
            { id: 'starts_with', label: 'Começa com' },
            { id: 'ends_with', label: 'Termina com' },
            { id: 'is_null', label: 'Está vazio' },
            { id: 'is_not_null', label: 'Não está vazio' }
        ],
        
        getOperatorsForField(field) {
            return this.operators;
        },
        
        getDefaultOperator(field) {
            return 'equals';
        },
        
        addCondition(group) {
            group.rules.push({
                field: 'name',
                operator: 'equals',
                value: ''
            });
        },
        
        addGroup(parentGroup) {
            parentGroup.rules.push({
                condition: 'AND',
                rules: [
                    {
                        field: 'name',
                        operator: 'equals',
                        value: ''
                    }
                ]
            });
        },
        
        removeRule(group, index) {
            group.rules.splice(index, 1);
        },
        
        countRules(group) {
            let count = 0;
            if(!group || !group.rules) return 0;
            for(let rule of group.rules) {
                if(rule.rules) {
                    count += this.countRules(rule);
                } else {
                    count++;
                }
            }
            return count;
        },
        
        naturalLanguage() {
            if(this.payload.rules.length === 0) {
                return 'Este segmento ainda não possui critérios.';
            }
            return 'Este segmento irá incluir os clientes <strong>' + (this.payload.condition === 'AND' ? 'que atendam a TODAS as regras abaixo' : 'que atendam a QUALQUER UMA das regras abaixo') + '</strong>.';
        },
        
        async runPreview() {
            if(this.payload.rules.length === 0) return;
            
            this.previewStatus = 'loading';
            
            try {
                const response = await fetch("{{ route('user.connect.segments.preview') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ payload: this.payload })
                });
                
                const data = await response.json();
                
                if(data.success) {
                    this.previewTotal = data.total;
                    this.previewContacts = data.contacts;
                    this.previewStatus = 'success';
                } else {
                    this.previewStatus = 'error';
                }
            } catch(e) {
                this.previewStatus = 'error';
                console.error(e);
            }
        },
        
        async saveSegment() {
            alert('A UI foi montada corretamente e a payload JSON está pronta para envio ao backend!');
            console.log(JSON.stringify(this.payload, null, 2));
        }
    }));
});
</script>
@endsection