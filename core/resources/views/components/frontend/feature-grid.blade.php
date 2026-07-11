@props(['columns' => '3'])

<style>
    .op-feature-grid {
        display: grid;
        gap: 40px 32px;
        margin: 64px 0;
    }
    
    @media (min-width: 768px) {
        .op-feature-grid-2 { grid-template-columns: repeat(2, 1fr); }
        .op-feature-grid-3 { grid-template-columns: repeat(3, 1fr); }
        .op-feature-grid-4 { grid-template-columns: repeat(4, 1fr); }
    }
    @media (max-width: 767px) {
        .op-feature-grid { grid-template-columns: 1fr; }
    }
    
    .op-feature-item {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    .op-feature-icon {
        color: var(--primary);
        font-size: 1.5rem;
        margin-bottom: 20px;
        background: rgba(124, 58, 237, 0.1);
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
    .op-feature-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 12px;
    }
    .op-feature-desc {
        font-size: 1.05rem;
        color: var(--text-muted);
        line-height: 1.6;
        margin: 0;
    }
</style>

<div {{ $attributes->merge(['class' => 'op-feature-grid op-feature-grid-' . $columns]) }}>
    {{ $slot }}
</div>
