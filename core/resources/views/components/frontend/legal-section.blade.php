@props(['id', 'title'])

<style>
    .docs-section { 
        margin-bottom: 56px; 
        scroll-margin-top: 120px; 
    }
    .docs-section h3 { 
        font-size: 1.8rem; 
        font-weight: 600; 
        color: #fff; 
        margin-bottom: 24px; 
        letter-spacing: -0.01em; 
        border-bottom: 1px solid var(--border); 
        padding-bottom: 16px; 
    }
    .docs-section p { 
        font-size: 1.1rem; 
        line-height: 1.8; 
        color: var(--text-base); 
        margin-bottom: 20px; 
    }
    .docs-section ul, .docs-section ol { 
        font-size: 1.1rem; 
        line-height: 1.8; 
        color: var(--text-base); 
        margin-bottom: 20px; 
        padding-left: 24px; 
    }
    .docs-section li { 
        margin-bottom: 8px; 
    }
</style>

<section id="{{ $id }}" class="docs-section">
    <h3>{{ $title }}</h3>
    {{ $slot }}
</section>
