@props(['title', 'desc'])

<style>
    .op-highlight-section {
        padding: 120px 20px;
        background: #000; /* Drenched black for contrast */
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        text-align: center;
    }
    .op-highlight-wrapper {
        max-width: 800px;
        margin: 0 auto;
    }
    .op-highlight-title {
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 700;
        color: #fff;
        margin-bottom: 24px;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .op-highlight-desc {
        font-size: 1.25rem;
        color: var(--text-muted);
        line-height: 1.6;
        margin: 0;
    }
</style>

<section class="op-highlight-section">
    <div class="op-highlight-wrapper">
        <h2 class="op-highlight-title">{{ $title }}</h2>
        @if(isset($desc))
            <p class="op-highlight-desc">{{ $desc }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
