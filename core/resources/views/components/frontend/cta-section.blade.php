@props(['title', 'subtitle', 'buttonText', 'buttonUrl'])

<style>
    .op-cta-section {
        padding: 100px 20px;
        text-align: center;
        background: var(--bg-panel);
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }
    .op-cta-wrapper {
        max-width: 600px;
        margin: 0 auto;
    }
    .op-cta-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 16px;
        letter-spacing: -0.02em;
    }
    .op-cta-subtitle {
        font-size: 1.15rem;
        color: var(--text-muted);
        margin-bottom: 40px;
        line-height: 1.6;
    }
</style>

<section class="op-cta-section">
    <div class="op-cta-wrapper">
        <h2 class="op-cta-title">{{ $title }}</h2>
        <p class="op-cta-subtitle">{{ $subtitle }}</p>
        <a href="{{ $buttonUrl }}" class="btn btn-primary btn-lg px-5">{{ $buttonText }}</a>
    </div>
</section>
