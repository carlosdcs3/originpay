@extends('backend.support_ticket.index')
@section('title', $title)
@section('support_header')
    <div class="clearfix my-3">
        <div class="fs-4 fw-semibold float-start">@yield('title')</div>
    </div>
@endsection
@section('support_content')
    {{-- KPIs --}}
    @php
        try {
            $openTickets = \App\Models\SupportTicket::where('status', \App\Enums\TicketStatus::OPEN)->count();
            $answeredTickets = \App\Models\SupportTicket::where('status', \App\Enums\TicketStatus::ANSWERED)->count();
            $closedTickets = \App\Models\SupportTicket::where('status', \App\Enums\TicketStatus::CLOSED)->count();
            $repliedTickets = \App\Models\SupportTicket::where('status', \App\Enums\TicketStatus::CUSTOMER_REPLY)->count();
        } catch(\Exception $e) {
            $openTickets = $answeredTickets = $closedTickets = $repliedTickets = '—';
        }
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Abertos" :value="$openTickets" icon="envelope-open" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Respondidos" :value="$answeredTickets" icon="check-circle" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Réplica do Cliente" :value="$repliedTickets" icon="reply" />
        </div>
        <div class="col-md-3 col-6">
            <x-ds.dev-stat-card title="Fechados" :value="$closedTickets" icon="lock" />
        </div>
    </div>

    <div class="card border-0 mb-4 shadow-sm rounded-3">
        <div class="card-body px-0 py-0">
            <div class="table-responsive">
                <table class="table user-table align-items-center mb-0">
                    <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('Title') .' | ' . __('User') }}</th>
                        <th>{{ __('Ticket ID') . ' | ' . __('Priority') }}</th>
                        <th>{{ __('Opening Time')  }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Status') }}</th>
                        @can('support-ticket-manage')
                            <th class="pe-4 text-end">{{ __('Action') }}</th>
                        @endcan
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($tickets as $ticket)
                        <tr class="align-middle">
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $ticket->title }}</div>
                                <div class="text-muted small">
                                  <a href="{{ route('admin.user.manage', $ticket->user->username) }}" class="text-decoration-none" style="color:var(--ds-accent);font-weight:500;">{{ title($ticket->user->name) }}</a>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">
                                    {{ strtoupper($ticket->uuid) }}
                                </div>
                                <div class="text-muted small mt-1">
                                    <span class="badge bg-{{ $ticket->priority->badgeColor() }} ">{{ $ticket->priority->label() }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $ticket->created_at->format('Y-m-d H:i') }}</div>
                                <div class="text-muted small mt-1">{{ $ticket->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="fw-bold text-dark">
                                {{ $ticket->category->name ?? __('Uncategorized')  }}
                            </td>
                            <td class="fw-bold text-uppercase">
                                <span class="badge bg-{{ $ticket->status->badgeColor() }} ">{{ $ticket->status->label() }}</span>
                                @if($ticket->is_resolved)
                                    <span class="badge bg-success ms-1">{{ __('Resolved') }}</span>
                                @endif
                            </td>
                            @can('support-ticket-manage')
                                <td class="pe-4 text-end">
                                    <a href="{{ route('admin.support-ticket.show', $ticket->id) }}" class="btn btn-secondary btn-sm" style="font-size:var(--ds-text-xs);padding:.25rem .5rem;"><x-icon name="chat" height="14" class="me-1"/>{{ __('Visualizar') }}</a>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <x-ds.empty-state 
                                    title="Nenhum ticket encontrado" 
                                    desc="Não há conversas ou chamados correspondentes aos filtros selecionados." 
                                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />' 
                                />
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
    </div>
@endsection
