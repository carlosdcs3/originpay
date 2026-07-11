@extends('backend.layouts.app')
@section('title', 'Conversa de Suporte')

@push('styles')
<style>
    .chat-container {
        display: flex;
        flex-direction: column;
        height: 70vh;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    .chat-header {
        padding: 16px 24px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }
    .chat-body {
        flex: 1;
        padding: 24px;
        overflow-y: auto;
        background: #fafafa;
    }
    .chat-footer {
        padding: 16px 24px;
        border-top: 1px solid #eee;
        background: #fff;
        border-radius: 0 0 8px 8px;
    }
    .chat-message {
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
    }
    .chat-message.admin {
        align-items: flex-end;
    }
    .chat-message.user {
        align-items: flex-start;
    }
    .chat-bubble {
        max-width: 75%;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.5;
        position: relative;
    }
    .chat-message.admin .chat-bubble {
        background: #00D4AA;
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    .chat-message.user .chat-bubble {
        background: #e9ecef;
        color: #333;
        border-bottom-left-radius: 4px;
    }
    .chat-meta {
        font-size: 11px;
        color: #999;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .chat-status-icon {
        font-size: 10px;
    }
    .chat-status-icon.read {
        color: #0d6efd; /* Blue */
    }
    .chat-status-icon.sent {
        color: #adb5bd; /* Gray */
    }
    .user-info-panel {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
        padding: 24px;
    }
    .info-item {
        margin-bottom: 16px;
    }
    .info-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="row">
    <!-- Chat Interface -->
    <div class="col-lg-8 mb-4">
        <div class="chat-container">
            <!-- Header -->
            <div class="chat-header">
                <div>
                    <h5 class="mb-1 fw-bold">{{ $user->fullname ?? $user->first_name ?? $user->name }}</h5>
                    <div class="text-muted" style="font-size: 13px;">
                        <i class="fas fa-circle text-success" style="font-size: 8px; margin-right: 4px;"></i> Online
                        &bull; {{ $user->email }}
                    </div>
                </div>
                <div>
                    <a href="{{ route('admin.support-chat.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    
                    <button class="btn btn-outline-primary btn-sm ms-2" onclick="navigator.clipboard.writeText('{{ route('admin.support-chat.show', $conversation->id) }}'); alert('Link copiado!');">
                        <i class="fas fa-link"></i> Copiar Link
                    </button>
                    
                    @if(!$conversation->isClosed())
                        <button type="button" class="btn btn-outline-danger btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#closeChatModal"><i class="fas fa-times-circle"></i> Encerrar Atendimento</button>
                    @else
                        <form action="{{ route('admin.support-chat.reopen', $conversation->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-success btn-sm ms-2" onclick="return dsConfirmForm(event, this.closest('form'), { title: 'Reabrir Atendimento', text: 'Deseja reabrir este atendimento?', confirmBtnText: 'Reabrir', confirmBtnClass: 'btn-success' })">
                                <i class="fas fa-undo"></i> Reabrir Atendimento
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Body -->
            <div class="chat-body" id="chatBody">
                <div id="chatMessages">
                    <!-- Messages will be loaded here via JS -->
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-spinner fa-spin fs-4 mb-2"></i>
                        <p>Carregando histÃ³rico...</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            @if(!$conversation->isClosed())
                <div class="chat-footer">
                    <form id="chatForm" enctype="multipart/form-data">
                        <div class="input-group">
                            <label class="btn btn-outline-secondary d-flex align-items-center" style="border-radius: 8px 0 0 8px; cursor: pointer;">
                                <i class="fas fa-paperclip"></i>
                                <input type="file" id="chatAttachment" name="attachment" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt,.log,.zip,.rar" style="display: none;">
                            </label>
                            <textarea class="form-control" id="chatInput" rows="2" placeholder="Escreva sua mensagem... (Shift+Enter para pular linha)" style="resize: none;"></textarea>
                            <button class="btn btn-primary px-4" type="submit" style="border-radius: 0 8px 8px 0;" id="sendBtn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div id="attachmentPreview" class="mt-2 text-muted" style="font-size: 0.85rem; display: none;">
                            <i class="fas fa-file-alt text-primary me-1"></i>
                            Anexo: <span id="attachmentName" class="fw-bold text-dark"></span>
                            <i class="fas fa-times text-danger ms-2" style="cursor:pointer;" onclick="clearAttachment()"></i>
                        </div>
                    </form>
                </div>
            @else
                <div class="chat-footer text-center py-4 bg-light">
                    <h6 class="text-muted mb-0"><i class="fas fa-lock me-2"></i>Este atendimento foi encerrado.</h6>
                    <div style="font-size: 0.85rem;" class="mt-2">
                        Encerrado em: {{ $conversation->closed_at ? $conversation->closed_at->format('d/m/Y \Ã \s H:i') : '' }} <br>
                        Administrador responsÃ¡vel: {{ optional($conversation->closedByAdmin)->name ?? 'Administrador' }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- User Info Context Panel -->
    <div class="col-lg-4">
        <div class="user-info-panel">
            <h5 class="fw-bold mb-4 border-bottom pb-2">InformaÃ§Ãµes do Cliente</h5>
            
            <div class="info-item">
                <div class="info-label">Nome Completo</div>
                <div class="info-value">{{ $user->fullname ?? $user->first_name ?? $user->name }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $user->email }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">ID / UUID</div>
                <div class="info-value">#{{ $user->id }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Data de Cadastro</div>
                <div class="info-value">{{ $user->created_at->format('d/m/Y H:i') }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Ãšltimo Login</div>
                <div class="info-value">{{ optional($user->latestLoginActivity)->created_at ? $user->latestLoginActivity->created_at->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Total de TransaÃ§Ãµes</div>
                <div class="info-value">{{ $user->transactions()->count() ?? 0 }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">API Key Principal</div>
                <div class="info-value">
                    @php
                        $firstKey = $user->apiKeys()->first();
                    @endphp
                    @if($firstKey)
                        {{ substr($firstKey->key, 0, 8) }}...{{ substr($firstKey->key, -4) }}
                    @else
                        Nenhuma chave gerada
                    @endif
                </div>
            </div>
            
            <div class="info-item mt-4">
                @if(Route::has('admin.users.detail'))
                    <a href="{{ route('admin.users.detail', $user->id) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-user-circle"></i> Ver Perfil Completo
                    </a>
                @elseif(Route::has('admin.user.manage'))
                    <a href="{{ route('admin.user.manage', $user->username ?? $user->id) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-user-circle"></i> Ver Perfil Completo
                    </a>
                @else
                    <span class="btn btn-sm btn-outline-secondary w-100 disabled">
                        <i class="fas fa-user-circle"></i> Ver Perfil Completo
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- Modal Encerrar Atendimento -->
<div class="modal fade" id="closeChatModal" tabindex="-1" aria-labelledby="closeChatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: var(--ds-surface-1); border: 1px solid var(--ds-border);">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title text-danger" id="closeChatModalLabel">Encerrar atendimento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="color: var(--ds-text-secondary);">
        Tem certeza que deseja encerrar esta conversa?
        <br><br>
        Após o encerramento:<br>
        &bull; o usuário não poderá mais responder nesta conversa;<br>
        &bull; ela será movida para os atendimentos encerrados;<br>
        &bull; esta ação poderá ser revertida apenas reabrindo o atendimento (caso exista essa funcionalidade).
      </div>
      <div class="modal-footer border-top-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmCloseChatBtn">Encerrar Atendimento</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
    const conversationId = {{ $conversation->id }};
    const fetchUrl = `{{ url('/admin/support-chat') }}/${conversationId}/fetch`;
    const sendUrl = `{{ route('admin.support-chat.reply', $conversation->id) }}`;
    const chatBody = document.getElementById('chatBody');
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatAttachment = document.getElementById('chatAttachment');
    const attachmentPreview = document.getElementById('attachmentPreview');
    const attachmentName = document.getElementById('attachmentName');
    
    let lastMessageId = 0;

    function clearAttachment() {
        if (chatAttachment) chatAttachment.value = '';
        if (attachmentPreview) attachmentPreview.style.display = 'none';
        if (attachmentName) attachmentName.innerText = '';
    }

    if (chatAttachment) {
        chatAttachment.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('O tamanho mÃ¡ximo permitido Ã© 5MB.');
                    clearAttachment();
                    return;
                }
                attachmentName.innerText = file.name;
                attachmentPreview.style.display = 'block';
            }
        });
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function renderMessage(msg) {
        const isAdmin = msg.sender === 'admin';
        const isSystem = msg.is_system || msg.sender === 'system';
        const cssClass = isSystem ? 'text-center my-3' : (isAdmin ? 'admin' : 'user');
        const time = formatTime(msg.created_at);
        
        if (isSystem) {
            return `
            <div class="chat-message ${cssClass}" id="msg-${msg.id}">
                <span class="badge bg-secondary text-light px-3 py-2 rounded-pill" style="font-size: 0.8rem; font-weight: 500;">
                    <i class="fas fa-info-circle me-1"></i> ${msg.message}
                </span>
                <div class="chat-meta justify-content-center mt-1" style="font-size: 10px;">
                    ${time}
                </div>
            </div>`;
        }

        let statusIcon = '';
        if (isAdmin) {
            if (msg.read_at) {
                statusIcon = `<i class="fas fa-check-double chat-status-icon read" title="Lida"></i>`;
            } else {
                statusIcon = `<i class="fas fa-check-double chat-status-icon sent" title="Enviada"></i>`;
            }
        }
        
        let attachmentHtml = '';
        if (msg.attachments && msg.attachments.length > 0) {
            msg.attachments.forEach(att => {
                const downloadUrl = att.url;
                if (att.mime_type.startsWith('image/')) {
                    const fallbackHtml = `<a href='${downloadUrl}' class='btn btn-sm btn-outline-primary mt-2' target='_blank' style='display:inline-block;'><i class='fas fa-download'></i> Baixar Imagem</a>`;
                    attachmentHtml += `
                        <div class="mt-2 text-center" style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 4px;">
                            <a href="${downloadUrl}" target="_blank">
                                <img src="${downloadUrl}" style="max-width: 100%; max-height: 200px; border-radius: 6px; cursor: pointer;" alt="${att.original_name}" onerror="this.onerror=null; this.outerHTML=\`${fallbackHtml}\`">
                            </a>
                        </div>
                    `;
                } else {
                    let icon = 'fa-file-alt';
                    if (att.mime_type === 'application/pdf') icon = 'fa-file-pdf';
                    else if (att.mime_type.includes('zip') || att.mime_type.includes('rar')) icon = 'fa-file-archive';
                    
                    attachmentHtml += `
                        <div class="mt-2 p-2 rounded d-flex align-items-center" style="background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1);">
                            <i class="fas ${icon} fs-4 me-2 text-primary"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="text-truncate" style="font-size: 0.85rem; max-width: 150px;" title="${att.original_name}">${att.original_name}</div>
                                <div style="font-size: 0.7rem; opacity: 0.8;">${(att.size / 1024).toFixed(1)} KB</div>
                            </div>
                            <a href="${downloadUrl}" class="btn btn-sm btn-outline-primary ms-2" target="_blank" download>
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    `;
                }
            });
        }

        const messageText = msg.message ? msg.message.replace(/\n/g, '<br>') : '';

        const html = `
            <div class="chat-message ${cssClass}" id="msg-${msg.id}">
                <div class="chat-bubble">
                    ${messageText}
                    ${attachmentHtml}
                </div>
                <div class="chat-meta">
                    ${time} ${statusIcon}
                </div>
            </div>
        `;
        return html;
    }

    async function fetchHistory(initial = false) {
        try {
            const response = await fetch(fetchUrl);
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                let hasNew = false;
                if (initial) chatMessages.innerHTML = ''; // clear loading
                
                data.messages.forEach(msg => {
                    if (msg.id > lastMessageId) {
                        chatMessages.innerHTML += renderMessage(msg);
                        lastMessageId = msg.id;
                        hasNew = true;
                    } else if (!initial && msg.sender === 'admin') {
                        // Update status if it changed to read
                        const msgEl = document.getElementById(`msg-${msg.id}`);
                        if (msgEl && msg.read_at && msgEl.innerHTML.includes('sent')) {
                            const icon = msgEl.querySelector('.chat-status-icon');
                            if (icon) {
                                icon.classList.remove('sent');
                                icon.classList.add('read');
                                icon.title = 'Lida';
                            }
                        }
                    }
                });
                
                if (hasNew || initial) {
                    scrollToBottom();
                }
            } else if (initial) {
                chatMessages.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <p>Nenhuma mensagem encontrada.</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching messages:', error);
        }
    }

    // Submit handler
    if (chatForm) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = chatInput.value.trim();
            const hasFile = chatAttachment && chatAttachment.files.length > 0;
            
            if (!msg && !hasFile) return;

            const formData = new FormData();
            if (msg) formData.append('message', msg);
            if (hasFile) formData.append('attachment', chatAttachment.files[0]);

            chatInput.value = ''; // clear
            clearAttachment();
            
            try {
                const sendBtn = document.getElementById('sendBtn');
                if (sendBtn) {
                    sendBtn.disabled = true;
                    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }

                const res = await fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        // Do not set Content-Type for FormData, fetch sets it automatically with boundary
                    },
                    body: formData
                });
                
                if (sendBtn) {
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                }

                const data = await res.json();
                if (data.success) {
                    fetchHistory(); // fetch immediately to show it
                } else {
                    alert('Erro: ' + (data.message || data.error || 'Falha ao enviar'));
                }
            } catch (error) {
                console.error('Error sending message:', error);
                const sendBtn = document.getElementById('sendBtn');
                if (sendBtn) {
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                }
            }
        });

        // Enter to send, Shift+Enter to newline
        chatInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });
    }

    // Init
    fetchHistory(true);

    // Polling every 5 seconds
    setInterval(() => fetchHistory(), 5000);
        const notifyIziToast = (message, type = 'success') => {
            if (typeof iziToast !== 'undefined') {
                if(type === 'success') iziToast.success({ message: message, position: "topRight" });
                else iziToast.error({ message: message, position: "topRight" });
            } else {
                alert(message);
            }
        };

        window.handleCloseConversation = function() {
            window.dsConfirm({
                title: 'Fechar Atendimento',
                text: 'Tem certeza que deseja encerrar este atendimento?<br><br>Após o encerramento o cliente não poderá mais responder nesta conversa. Caso necessário, um administrador poderá reabri-la posteriormente.',
                confirmBtnText: 'Fechar Atendimento',
                confirmBtnClass: 'btn-danger',
                ajax: true,
                onConfirm: () => {
                    const form = document.getElementById('formCloseConversation');
                    return fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'Accept': 'application/json' }
                    }).then(res => res.json()).then(data => {
                        if(data.success) {
                            notifyIziToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            notifyIziToast(data.message || 'Erro ao fechar.', 'error');
                        }
                    }).catch(() => notifyIziToast('Erro de conexão.', 'error'));
                }
            });
        };

        window.handleReopenConversation = function() {
            window.dsConfirm({
                title: 'Reabrir Atendimento',
                text: 'Deseja reabrir este atendimento?',
                confirmBtnText: 'Reabrir',
                confirmBtnClass: 'btn-success',
                ajax: true,
                onConfirm: () => {
                    const form = document.getElementById('formReopenConversation');
                    return fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'Accept': 'application/json' }
                    }).then(res => res.json()).then(data => {
                        if(data.success) {
                            notifyIziToast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            notifyIziToast(data.message || 'Erro ao reabrir.', 'error');
                        }
                    }).catch(() => notifyIziToast('Erro de conexão.', 'error'));
                }
            });
        };

        // Lógica do Modal de Encerrar Atendimento
        const confirmCloseBtn = document.getElementById('confirmCloseChatBtn');
        if (confirmCloseBtn) {
            confirmCloseBtn.addEventListener('click', async function() {
                const btn = this;
                const originalText = btn.innerHTML;
                
                // UI Loading state
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Encerrando...';
                
                try {
                    const response = await fetch("{{ route('admin.support-chat.close', $conversation->id) }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        // Fechar modal
                        const modalEl = document.getElementById('closeChatModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }

                        // Exibir Toast Sucesso
                        notifyIziToast('Atendimento encerrado com sucesso.', 'success');
                        
                        // Atualizar interface sem F5
                        
                        // Esconder painel de resposta
                        const replyForm = document.getElementById('chatForm');
                        if(replyForm) replyForm.style.display = 'none';
                        
                        // Atualizar Status Badge
                        const statusContainer = document.getElementById('chat-status-container');
                        if(statusContainer) {
                            statusContainer.innerHTML = '<span class="badge bg-danger">Fechado</span>';
                        }
                        
                        // Recarregar histórico para mostrar mensagem de sistema
                        fetchHistory();
                        
                        // Substituir botão "Fechar Atendimento" por "Reabrir Atendimento" (opcional/simplificado)
                        const actionsDiv = document.querySelector('.card-header > div:last-child');
                        if(actionsDiv) {
                            const closeBtn = actionsDiv.querySelector('[data-bs-target="#closeChatModal"]');
                            if(closeBtn) closeBtn.remove();
                            // Injetamos um botão estático para reabrir caso necessário
                            actionsDiv.insertAdjacentHTML('beforeend', `
                                <form action="{{ route('admin.support-chat.reopen', $conversation->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-sm ms-2" onclick="return window.dsConfirm({ title: 'Reabrir Atendimento', text: 'Deseja reabrir este atendimento?', confirmBtnText: 'Reabrir', confirmBtnClass: 'btn-success', ajax: false })">
                                        <i class="fas fa-undo"></i> Reabrir Atendimento
                                    </button>
                                </form>
                            `);
                        }
                        
                    } else {
                        throw new Error(data.message || 'Falha na requisição');
                    }
                } catch(err) {
                    console.error(err);
                    notifyIziToast('Erro ao encerrar o atendimento.', 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        }
    </script>
@endpush
