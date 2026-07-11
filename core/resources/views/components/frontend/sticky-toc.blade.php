@props(['sections' => []])

<style>
    .docs-container { max-width: 1200px; margin: 0 auto; display: flex; gap: 40px; padding: 40px 20px; align-items: flex-start; }
    .docs-main { max-width: 800px; flex-grow: 1; }
    
    /* Sidebar Index */
    .docs-sidebar-wrapper { width: 260px; flex-shrink: 0; position: sticky; top: 100px; align-self: flex-start; }
    .docs-sidebar { padding-right: 20px; }
    .docs-nav-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600; margin-bottom: 16px; }
    .docs-nav-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; border-left: 1px solid var(--border); }
    .docs-nav-link { display: block; padding: 6px 0 6px 16px; color: var(--text-muted); text-decoration: none; font-size: 0.95rem; transition: all 0.2s; position: relative; left: -1px; border-left: 2px solid transparent; }
    .docs-nav-link:hover { color: var(--primary-light); }
    .docs-nav-link.active { color: var(--primary); font-weight: 500; border-left-color: var(--primary); }

    /* Mobile Accordion */
    .docs-mobile-nav { display: none; background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; margin-bottom: 32px; overflow: hidden; }
    .docs-mobile-nav-toggle { width: 100%; text-align: left; background: none; border: none; padding: 16px; color: #fff; font-weight: 500; display: flex; justify-content: space-between; align-items: center; }
    .docs-mobile-nav-content { padding: 0 16px 16px; display: none; }
    .docs-mobile-nav.open .docs-mobile-nav-content { display: block; }

    @media (max-width: 991px) {
        .docs-sidebar-wrapper { width: 220px; }
        .docs-main { max-width: 100%; }
    }
    @media (max-width: 768px) {
        .docs-container { flex-direction: column; padding: 24px 16px; gap: 0; }
        .docs-sidebar-wrapper { display: none; }
        .docs-mobile-nav { display: block; }
    }
</style>

<!-- Mobile Accordion -->
<div class="docs-mobile-nav" id="mobileDocsNavContainer">
    <button class="docs-mobile-nav-toggle" id="mobileDocsNavToggle">
        <span>Índice</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div class="docs-mobile-nav-content">
        <ul class="docs-nav-list" id="mobileDocsNav">
            @foreach($sections as $id => $label)
                <li><a href="#{{ $id }}" class="docs-nav-link" data-target="{{ $id }}">{{ $label }}</a></li>
            @endforeach
        </ul>
    </div>
</div>

<!-- Desktop Sidebar -->
<aside class="docs-sidebar-wrapper">
    <div class="docs-sidebar">
        <div class="docs-nav-title">Nesta página</div>
        <ul class="docs-nav-list" id="desktopDocsNav">
            @foreach($sections as $id => $label)
                <li><a href="#{{ $id }}" class="docs-nav-link" data-target="{{ $id }}">{{ $label }}</a></li>
            @endforeach
        </ul>
    </div>
</aside>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Accordion Toggle
    const toggleBtn = document.getElementById('mobileDocsNavToggle');
    const container = document.getElementById('mobileDocsNavContainer');
    if(toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            container.classList.toggle('open');
        });
    }

    // Smooth Scroll & Active State Logic
    const links = document.querySelectorAll('.docs-nav-link');
    const sections = document.querySelectorAll('.docs-section');
    
    // Click behavior
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const targetSection = document.getElementById(targetId);
            
            if(targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth' });
                history.pushState(null, null, '#' + targetId);
                if(container) container.classList.remove('open');
            }
        });
    });

    // Intersection Observer for Scroll Spy
    const observerOptions = {
        root: null,
        rootMargin: '-100px 0px -60% 0px',
        threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                links.forEach(link => {
                    if(link.getAttribute('data-target') === id) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }
        });
    }, observerOptions);

    sections.forEach(section => observer.observe(section));
});
</script>
@endpush
