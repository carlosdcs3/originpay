@extends('frontend.layouts.user-v2')
@section('title', __('Support Tickets'))

@section('content')

{{-- Page Header --}}
<div class="ds-page-header">
    <h4 style="font-size:1.1rem;font-weight:700;margin:0 0 2px;color:var(--ds-text-primary);">
        <i class="fas fa-headset" style="color:var(--ds-teal);margin-right:8px;font-size:0.95rem;"></i>
        Meus Tickets
    </h4>
    <p style="color:var(--ds-text-muted);font-size:0.8rem;margin:0;">Acompanhe ou inicie um atendimento com nosso suporte</p>
</div>

<div class="ds-card">
    <div class="ds-card-header d-flex justify-content-between align-items-center">
        <span class="ds-v2-card-header m-0">
            <i class="fas fa-list" style="color:var(--ds-teal);margin-right:6px;"></i>
            {{ __('Support Tickets') }}
        </span>
        <a class="ds-btn-submit" href="{{ route('user.support-ticket.create') }}" style="width: auto; padding: 6px 16px; font-size: 0.85rem; background: var(--ds-teal); color: #05100D; border-radius: 8px;">
            <i class="fa-solid fa-plus"></i> {{ __('Create Ticket') }}
        </a>
    </div>
    
    <div class="ds-card-body p-0">
        <div class="ticket-list" style="padding: 16px;">
            @forelse($tickets as $ticket)
                <div class="ticket-item" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 16px; margin-bottom: 12px; display: flex; align-items: center; transition: all 0.2s;">
                    <div class="ticket-details flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <a href="{{ route('user.support-ticket.show', $ticket->id) }}"
                                   style="color: #fff; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    {{ $ticket->title }}
                                    <span class="badge" style="background: rgba(124, 110, 255, 0.1); color: #7C6EFF; border: 1px solid rgba(124, 110, 255, 0.2); font-size: 0.7rem;">
                                        <i class="fa-solid fa-bolt"></i> {{ $ticket->priority->label() }}
                                    </span>
                                </a>
                                <div style="color: #94a3b8; font-size: 0.8rem; display: flex; gap: 12px; align-items: center;">
                                    <span><i class="fas fa-hashtag"></i> {{ $ticket->uuid }}</span>
                                    @if($ticket->isReplied())
                                        <span class="badge" style="background: rgba(0, 212, 170, 0.1); color: #00D4AA; border: 1px solid rgba(0, 212, 170, 0.2);">
                                            <i class="fa-light fa-comment"></i> {{ __('Answered') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <span class="badge mb-2" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                                    {{ $ticket->status->label() }}
                                </span>
                                <div style="color: #94a3b8; font-size: 0.75rem;">
                                    <i class="far fa-clock"></i> {{ $ticket->created_at->format('d M Y, h:i A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4" style="color: #94a3b8;">
                    <i class="fas fa-inbox mb-3" style="font-size: 2rem; opacity: 0.5;"></i>
                    <p class="m-0">{{ __('No tickets found') }}</p>
                </div>
            @endforelse
        </div>
        
        @if($tickets->hasPages())
            <div class="p-3" style="border-top: 1px solid rgba(255,255,255,0.05);">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection