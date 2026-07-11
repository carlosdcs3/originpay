@props(['icon', 'title', 'subtitle', 'actionLabel', 'actionUrl', 'isPrimary' => false])

<style>
    .op-contact-card {
        background: var(--bg-panel);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 40px;
        text-align: left;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: border-color 0.2s;
    }
    .op-contact-card:hover {
        border-color: var(--primary);
    }
    .op-contact-card.is-primary {
        background: rgba(124, 58, 237, 0.02);
        border-color: rgba(124, 58, 237, 0.3);
    }
    .op-contact-icon {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 24px;
    }
    .op-contact-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 12px;
    }
    .op-contact-subtitle {
        font-size: 1.05rem;
        color: var(--text-muted);
        line-height: 1.6;
        margin-bottom: 32px;
        flex-grow: 1;
    }
</style>

<div class="op-contact-card {{ $isPrimary ? 'is-primary' : '' }}">
    <div class="op-contact-icon">
        <i class="{{ $icon }}"></i>
    </div>
    <h3 class="op-contact-title">{{ $title }}</h3>
    <p class="op-contact-subtitle">{{ $subtitle }}</p>
    <div>
        <a href="{{ $actionUrl }}" class="btn {{ $isPrimary ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $actionLabel }}</a>
    </div>
</div>
