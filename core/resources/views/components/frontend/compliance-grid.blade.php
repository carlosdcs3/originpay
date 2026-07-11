@props(['items' => []])

<style>
    .op-compliance-grid {
        padding: 80px 20px;
        background: var(--bg-deep);
    }
    .op-compliance-wrapper {
        max-width: 1000px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }
    @media (min-width: 768px) {
        .op-compliance-wrapper {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .op-compliance-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        padding: 32px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-panel);
    }
    .op-compliance-icon {
        font-size: 1.5rem;
        color: var(--text-muted);
        flex-shrink: 0;
    }
    .op-compliance-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 8px;
    }
    .op-compliance-desc {
        font-size: 0.95rem;
        color: var(--text-muted);
        line-height: 1.6;
        margin: 0;
    }
</style>

<div class="op-compliance-grid">
    <div class="op-compliance-wrapper">
        @foreach($items as $item)
            <div class="op-compliance-item">
                <div class="op-compliance-icon"><i class="{{ $item['icon'] ?? 'fas fa-shield-alt' }}"></i></div>
                <div>
                    <h3 class="op-compliance-title">{{ $item['title'] }}</h3>
                    <p class="op-compliance-desc">{{ $item['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
