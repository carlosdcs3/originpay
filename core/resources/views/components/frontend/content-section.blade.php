@props(['maxWidth' => '800px', 'padding' => '80px 20px'])

<style>
    .op-content-section {
        padding: {{ $padding }};
        background: var(--bg-deep);
        color: var(--text-base);
    }
    .op-content-wrapper {
        max-width: {{ $maxWidth }};
        margin: 0 auto;
        width: 100%;
        line-height: 1.8;
        font-size: 1.1rem;
    }
    
    /* Typography inside content section */
    .op-content-wrapper h2 {
        font-size: 2.2rem;
        font-weight: 600;
        color: #fff;
        margin: 48px 0 24px;
        letter-spacing: -0.02em;
    }
    .op-content-wrapper h3 {
        font-size: 1.6rem;
        font-weight: 600;
        color: #fff;
        margin: 40px 0 20px;
        letter-spacing: -0.01em;
    }
    .op-content-wrapper p {
        margin-bottom: 24px;
        color: var(--text-base);
    }
    .op-content-wrapper ul, 
    .op-content-wrapper ol {
        margin-bottom: 32px;
        padding-left: 24px;
    }
    .op-content-wrapper li {
        margin-bottom: 12px;
    }
    .op-content-wrapper img {
        max-width: 100%;
        border-radius: 12px;
        margin: 32px 0;
        border: 1px solid var(--border);
    }
</style>

<section {{ $attributes->merge(['class' => 'op-content-section']) }}>
    <div class="op-content-wrapper">
        {{ $slot }}
    </div>
</section>
