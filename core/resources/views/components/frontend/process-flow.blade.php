@props(['steps' => []])

<style>
    .op-process-flow {
        padding: 80px 20px;
        background: var(--bg-deep);
    }
    .op-process-wrapper {
        max-width: 800px;
        margin: 0 auto;
    }
    .op-process-step {
        display: flex;
        gap: 32px;
        margin-bottom: 48px;
        position: relative;
    }
    .op-process-step:last-child {
        margin-bottom: 0;
    }
    .op-process-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--bg-panel);
        border: 1px solid var(--border);
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        font-size: 0.9rem;
        flex-shrink: 0;
        z-index: 2;
    }
    .op-process-step::after {
        content: '';
        position: absolute;
        top: 40px;
        left: 19.5px;
        bottom: -48px;
        width: 1px;
        background: var(--border);
        z-index: 1;
    }
    .op-process-step:last-child::after {
        display: none;
    }
    .op-process-content {
        padding-top: 8px;
    }
    .op-process-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 12px;
        letter-spacing: -0.01em;
    }
    .op-process-desc {
        font-size: 1.1rem;
        color: var(--text-muted);
        line-height: 1.7;
        margin: 0;
    }
</style>

<div class="op-process-flow">
    <div class="op-process-wrapper">
        @foreach($steps as $index => $step)
            <div class="op-process-step">
                <div class="op-process-number">{{ sprintf('%02d', $index + 1) }}</div>
                <div class="op-process-content">
                    <h3 class="op-process-title">{{ $step['title'] }}</h3>
                    <p class="op-process-desc">{{ $step['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
