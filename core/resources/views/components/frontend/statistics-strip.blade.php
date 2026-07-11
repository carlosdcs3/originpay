@props(['stats' => []])

<style>
    .op-stats-strip {
        background: var(--bg-deep);
        padding: 64px 20px;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }
    .op-stats-grid {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 40px;
        text-align: center;
    }
    .op-stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .op-stat-value {
        font-size: 3rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 8px;
        letter-spacing: -0.03em;
        line-height: 1;
    }
    .op-stat-label {
        font-size: 1.05rem;
        color: var(--text-muted);
        font-weight: 500;
    }
</style>

<div class="op-stats-strip">
    <div class="op-stats-grid">
        @foreach($stats as $stat)
            <div class="op-stat-item">
                <div class="op-stat-value">{{ $stat['value'] }}</div>
                <div class="op-stat-label">{{ $stat['label'] }}</div>
            </div>
        @endforeach
        
        {{ $slot }}
    </div>
</div>
