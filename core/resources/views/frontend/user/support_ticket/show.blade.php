@extends('frontend.layouts.user-v2')
@section('title', __('Support Ticket Details'))

@section('content')

{{-- Page Header --}}
<div class="ds-page-header">
    <h4 style="font-size:1.1rem;font-weight:700;margin:0 0 2px;color:var(--ds-text-primary);">
        <i class="fas fa-ticket-alt" style="color:var(--ds-teal);margin-right:8px;font-size:0.95rem;"></i>
        Detalhes do Ticket
    </h4>
    <p style="color:var(--ds-text-muted);font-size:0.8rem;margin:0;">Visualizando ticket de suporte: {{ $ticket->uuid }}</p>
</div>

<div class="ds-card">
    <div class="ds-card-header d-flex justify-content-between align-items-center">
        <span class="ds-v2-card-header m-0">
            <i class="fas fa-comments" style="color:var(--ds-teal);margin-right:6px;"></i>
            {{ __('Conversa') }}
        </span>
        <a class="ds-btn-submit" href="{{ route('user.support-ticket.index') }}" style="width: auto; padding: 6px 16px; font-size: 0.85rem; background: rgba(255,255,255,0.05); color: #fff; border-radius: 8px;">
            <i class="fa-solid fa-arrow-left"></i> {{ __('Voltar') }}
        </a>
    </div>

    <div class="ds-card-body p-0">
        {{-- Header Info --}}
        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex gap-2">
                    <span class="badge" style="background: rgba(124, 110, 255, 0.1); color: #7C6EFF; border: 1px solid rgba(124, 110, 255, 0.2); font-size: 0.75rem;">
                        <i class="fa-solid fa-bolt"></i> {{ $ticket->priority->label() }}
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); font-size: 0.75rem;">
                        {{ $ticket->status->label() }}
                    </span>
                </div>
                <div style="color: #94a3b8; font-size: 0.8rem;">
                    {{ $ticket->created_at->format('d M Y, H:i') }}
                </div>
            </div>
            <h5 style="color: #fff; font-weight: 700; margin: 10px 0 5px;">{{ $ticket->title }}</h5>
            <p style="color: #94a3b8; font-size: 0.95rem; margin: 0; line-height: 1.5;">{!! nl2br(e($ticket->message)) !!}</p>
        </div>

        {{-- Chat Section --}}
        <div class="p-4" style="background: #12141c; min-height: 200px; max-height: 500px; overflow-y: auto;">
            @if($ticket->messages->count() > 0)
                @foreach($ticket->messages as $message)
                    <div class="d-flex {{ $message->admin_id ? 'justify-content-start' : 'justify-content-end' }} mb-4">
                        @if($message->admin_id)
                            <img src="{{ asset(auth()->user()->avatar_alt) }}" class="rounded-circle me-3" alt="Admin" width="40" height="40" style="border: 2px solid rgba(0, 212, 170, 0.3);">
                        @endif
                        
                        <div style="max-width: 80%;">
                            <div style="
                                background: {{ $message->admin_id ? 'rgba(255,255,255,0.03)' : 'var(--ds-teal)' }};
                                color: {{ $message->admin_id ? '#fff' : '#05100D' }};
                                padding: 14px 18px;
                                border-radius: {{ $message->admin_id ? '0 16px 16px 16px' : '16px 0 16px 16px' }};
                                font-size: 0.95rem;
                                line-height: 1.5;
                                border: {{ $message->admin_id ? '1px solid rgba(255,255,255,0.05)' : 'none' }};
                            ">
                                <p class="mb-0">{!! nl2br(e($message->message)) !!}</p>
                                @if($message->attachment)
                                    <a href="{{ route('file.download', ['filePath' => $message->attachment]) }}"
                                       class="d-inline-flex align-items-center mt-2"
                                       style="color: {{ $message->admin_id ? 'var(--ds-teal)' : '#05100D' }}; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 6px 12px; background: rgba(0,0,0,0.1); border-radius: 6px;">
                                        <i class="fas fa-download" style="margin-right: 6px;"></i> {{ __('Download Anexo') }}
                                    </a>
                                @endif
                            </div>
                            <div class="mt-2 {{ $message->admin_id ? 'text-start ms-2' : 'text-end me-2' }}" style="font-size: 0.75rem; color: #64748b;">
                                {{ $message->admin_id ? 'Suporte OriginPay' : $ticket->user->name }} â€¢ {{ $message->created_at->format('H:i') }}
                            </div>
                        </div>

                        @if(!$message->admin_id)
                            <img src="{{ asset($ticket->user->avatar_alt) }}" class="rounded-circle ms-3" alt="User" width="40" height="40" style="border: 2px solid rgba(255, 255, 255, 0.1);">
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center" style="color: #64748b; padding: 40px 0;">
                    <p class="m-0">{{ __('No messages yet.') }}</p>
                </div>
            @endif
        </div>

        {{-- Reply Form --}}
        <div class="p-4" style="border-top: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2);">
            <form action="{{ route('user.support-ticket.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="ds-form-group">
                    <textarea name="message" class="v2-input" rows="3" placeholder="Digite sua mensagem aqui..." required style="resize: none;"></textarea>
                </div>

                {{-- File Preview --}}
                <div class="mb-3 d-none" id="attachment-preview">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
                        <div class="d-flex align-items-center" style="color: #94a3b8;">
                            <i class="fas fa-file me-2" style="color: var(--ds-teal);"></i>
                            <span id="file-name" style="font-size: 0.85rem;" class="text-truncate"></span>
                        </div>
                        <button type="button" onclick="removeFile()" style="background: none; border: none; color: #FF4D6A; font-size: 0.85rem; padding: 0; cursor: pointer;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <div style="flex: 1;">
                        <input type="file" id="file-input" name="attachment" class="d-none" onchange="handleFileChange()">
                        <label for="file-input" class="ds-btn-submit m-0" style="background: rgba(255,255,255,0.05); color: #fff; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-paperclip" style="margin-right: 8px;"></i> Anexar
                        </label>
                    </div>
                    <div style="flex: 3;">
                        <button type="submit" class="ds-btn-submit m-0" style="background: var(--ds-teal); color: #05100D;">
                            <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Enviar Mensagem
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('frontend.user.support_ticket.partials._script')
@endpush
