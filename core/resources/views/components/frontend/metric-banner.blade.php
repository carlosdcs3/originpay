@props(['metric', 'label'])

<style>
    .op-metric-banner {
        padding: 140px 20px;
        background: var(--bg-deep);
        text-align: center;
        border-top: 1px solid rgba(255,255,255,0.05);
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .op-metric-value {
        font-size: clamp(3.5rem, 8vw, 7rem);
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.04em;
        margin-bottom: 24px;
        font-family: 'Inter', sans-serif;
        color: var(--text-primary); /* Solid color instead of gradient slop */
    }
    .op-metric-label {
        font-size: clamp(1.1rem, 2vw, 1.4rem);
        color: var(--text-muted);
        font-weight: 400;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
</style>

<section class="op-metric-banner">
    <div class="op-metric-value">{{ $metric }}</div>
    <div class="op-metric-label">{{ $label }}</div>
</section>
