@extends('backend.layouts.app')
@section('title', 'Conversas de Suporte')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Conversas de Suporte</h5>
            </div>
            <div class="card-body p-0">
                @if(isset($conversations) && $conversations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Assunto / Usuário</th>
                                <th>Última Mensagem</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($conversations as $conversation)
                                @php
                                    $user = $conversation->user;
                                    $lastChat = optional($conversation->messages)->first();
                                    $hasUnread = ($conversation->unread_count ?? 0) > 0;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $conversation->subject ?? 'Conversa' }}</div>
                                        <div class="d-flex align-items-center mt-1">
                                            <div class="me-2">
                                                <img src="{{ $user->avatar_alt ?? '' }}" alt="Avatar" class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                                            </div>
                                            <div class="text-muted" style="font-size: 12px;">
                                                {{ $user->fullname ?? $user->name ?? 'Desconhecido' }} &bull; {{ $user->email ?? 'Sem email' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($lastChat)
                                            <div class="text-truncate text-muted" style="max-width: 350px; font-size: 14px;">
                                                {{ \Illuminate\Support\Str::limit($lastChat->message, 80) }}
                                            </div>
                                            <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                                                {{ optional($lastChat->created_at)->diffForHumans() ?? 'Data desconhecida' }}
                                            </div>
                                        @else
                                            <span class="text-muted" style="font-size: 14px;">Sem mensagens</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="mb-1">
                                            @if($conversation->status === 'open')
                                                <span class="badge bg-success rounded-pill px-2 py-1">Aberta</span>
                                            @elseif($conversation->status === 'pending')
                                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1">Aguardando</span>
                                            @elseif($conversation->status === 'answered')
                                                <span class="badge bg-primary rounded-pill px-2 py-1">Respondida</span>
                                            @elseif($conversation->status === 'closed')
                                                <span class="badge bg-secondary rounded-pill px-2 py-1">Encerrada</span>
                                            @endif
                                        </div>
                                        @if($hasUnread)
                                            <div class="text-danger" style="font-size: 11px; font-weight: bold;">
                                                {{ $conversation->unread_count }} não lida(s)
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.support-chat.show', $conversation->id) }}" class="btn btn-sm btn-primary">
                                            Responder
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="cil-chat-bubble fs-1 mb-3" style="font-size: 48px;"></i>
                    <p class="mb-0 fs-5">Nenhuma conversa de suporte encontrada.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
