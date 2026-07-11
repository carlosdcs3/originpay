@extends('frontend.layouts.user-v2')
@section('title', __('New Support Ticket'))

@section('content')
{{-- Page Header --}}
<div class="ds-page-header">
    <h4 style="font-size:1.1rem;font-weight:700;margin:0 0 2px;color:var(--ds-text-primary);">
        <i class="fas fa-ticket-alt" style="color:var(--ds-teal);margin-right:8px;font-size:0.95rem;"></i>
        Abrir Novo Ticket
    </h4>
    <p style="color:var(--ds-text-muted);font-size:0.8rem;margin:0;">Como podemos ajudar você hoje?</p>
</div>

<div class="ds-card">
    <div class="ds-card-header d-flex justify-content-between align-items-center">
        <span class="ds-v2-card-header m-0">
            <i class="fas fa-edit" style="color:var(--ds-teal);margin-right:6px;"></i>
            {{ __('New Support Ticket') }}
        </span>
        <a class="ds-btn-submit" href="{{ route('user.support-ticket.index') }}" style="width: auto; padding: 6px 16px; font-size: 0.85rem; background: rgba(255,255,255,0.05); color: #fff; border-radius: 8px;">
            <i class="fa-solid fa-arrow-left"></i> {{ __('Meus Tickets') }}
        </a>
    </div>

    <div class="ds-card-body padded">
        <form action="{{ route('user.support-ticket.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="ds-form-group">
                        <label for="title" class="ds-label">{{ __('Ticket Title') }}</label>
                        <input type="text"
                               class="v2-input @error('title') is-invalid @enderror"
                               id="title"
                               name="title"
                               placeholder="Ex: Problema com depósito PIX"
                               value="{{ old('title') }}"
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ds-form-group">
                        <label for="category_id" class="ds-label">{{ __('Category (Optional)') }}</label>
                        <select class="v2-input"
                                id="category_id"
                                name="category_id">
                            <option value="" disabled selected>{{ __('Select Category') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ ucfirst($category->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="ds-form-group mt-3">
                <label for="message" class="ds-label">{{ __('Message') }}</label>
                <textarea class="v2-input @error('message') is-invalid @enderror"
                          id="message"
                          name="message"
                          rows="5"
                          placeholder="Descreva seu problema com o máximo de detalhes possível..."
                          required>{{ old('message') }}</textarea>
                @error('message')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="ds-form-group">
                        <label for="attachments" class="ds-label">{{ __('Attach Files (Optional)') }}</label>
                        <input type="file"
                               class="v2-input @error('attachments') is-invalid @enderror"
                               id="attachments"
                               name="attachment"
                               multiple>
                        <span class="ds-field-hint">JPG, PNG, PDF. Max 2MB.</span>
                        @error('attachments')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ds-form-group">
                        <label for="priority" class="ds-label">{{ __('Priority') }}</label>
                        <select class="v2-input @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                            @foreach(\App\Enums\TicketPriority::options() as $priority)
                                <option value="{{ $priority['value'] }}" {{ old('priority') == $priority['value'] ? 'selected' : '' }}>
                                    {{ $priority['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="ds-btn-submit" style="background:var(--ds-teal);color:#05100D;">
                    <i class="fas fa-paper-plane" style="margin-right:8px;"></i> {{ __('Submit Support Ticket') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection