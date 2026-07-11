@props(['quote', 'author', 'role'])

<style>
    .op-editorial-quote {
        padding: 120px 20px;
        text-align: center;
        background: transparent;
        border-top: 1px solid rgba(255,255,255,0.05);
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .op-editorial-quote-wrapper {
        max-width: 760px;
        margin: 0 auto;
    }
    .op-editorial-quote-text {
        font-family: 'Inter', sans-serif;
        font-size: clamp(1.5rem, 3.5vw, 2.2rem);
        font-weight: 500;
        color: #e2e8f0; /* softer white */
        line-height: 1.5;
        letter-spacing: -0.02em;
        margin-bottom: 40px;
    }
    .op-editorial-quote-author {
        font-size: 1rem;
        font-weight: 600;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    .op-editorial-quote-role {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-top: 6px;
    }
</style>

<section class="op-editorial-quote">
    <div class="op-editorial-quote-wrapper">
        <blockquote class="op-editorial-quote-text">
            "{{ $quote }}"
        </blockquote>
        @if(isset($author))
            <div class="op-editorial-quote-author">{{ $author }}</div>
            <div class="op-editorial-quote-role">{{ $role ?? '' }}</div>
        @endif
    </div>
</section>
