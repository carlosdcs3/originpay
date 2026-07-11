@props(['title', 'subtitle', 'breadcrumb' => null])

<style>
    .op-editorial-hero {
        padding: 100px 20px 40px;
        background: var(--bg-deep);
        border-bottom: 1px solid var(--border);
        text-align: {{ isset($attributes['align']) ? $attributes['align'] : 'center' }};
        min-height: 220px;
        display: flex;
        align-items: center;
    }
    .op-editorial-hero-content {
        max-width: 800px;
        margin: 0 auto;
        width: 100%;
    }
    .op-editorial-breadcrumb {
        color: var(--text-muted);
        font-size: 0.95rem;
        font-weight: 500;
        margin-bottom: 16px;
        letter-spacing: 0.02em;
    }
    .op-editorial-breadcrumb-sep {
        margin: 0 8px;
        opacity: 0.5;
    }
    .op-editorial-title {
        font-size: clamp(2.5rem, 5vw, 3.5rem);
        font-weight: 700;
        letter-spacing: -0.03em;
        color: #fff;
        margin-bottom: 20px;
        line-height: 1.1;
    }
    .op-editorial-subtitle {
        font-size: clamp(1.1rem, 2vw, 1.35rem);
        color: var(--text-muted);
        line-height: 1.6;
        margin: 0 auto;
        font-weight: 400;
    }
</style>

<section {{ $attributes->merge(['class' => 'op-editorial-hero']) }}>
    <div class="op-editorial-hero-content">
        @if($breadcrumb)
            <div class="op-editorial-breadcrumb">
                {!! str_replace('/', '<span class="op-editorial-breadcrumb-sep">/</span>', $breadcrumb) !!}
            </div>
        @endif
        
        <h1 class="op-editorial-title">{{ $title }}</h1>
        
        @if($subtitle)
            <p class="op-editorial-subtitle">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
