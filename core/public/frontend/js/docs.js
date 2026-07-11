document.addEventListener('DOMContentLoaded', () => {
    // Event Delegation for Code Tabs & Copy Buttons
    document.addEventListener('click', (e) => {
        // Tab Switching
        const tab = e.target.closest('.doc-code-tab');
        if (tab) {
            const target = tab.getAttribute('data-target');
            const container = tab.closest('.doc-code-block');
            
            // Reset active tabs
            container.querySelectorAll('.doc-code-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Hide all content
            container.querySelectorAll('.doc-code-content').forEach(c => c.style.display = 'none');
            
            // Show target content
            const targetEl = container.querySelector('#' + target);
            if(targetEl) targetEl.style.display = 'block';
            return;
        }

        // Copy Button
        const copyBtn = e.target.closest('.doc-code-copy');
        if (copyBtn) {
            const container = copyBtn.closest('.doc-code-block');
            const visibleContent = Array.from(container.querySelectorAll('.doc-code-content')).find(c => c.style.display !== 'none');
            
            if (visibleContent) {
                const code = visibleContent.innerText;
                navigator.clipboard.writeText(code).then(() => {
                    const originalHTML = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="fas fa-check"></i> Copiado';
                    copyBtn.style.color = 'var(--doc-primary)';
                    
                    setTimeout(() => {
                        copyBtn.innerHTML = originalHTML;
                        copyBtn.style.color = '';
                    }, 2000);
                });
            }
        }
    });

    // Intersection Observer for Right TOC (ScrollSpy)
    const tocLinks = document.querySelectorAll('.doc-toc-list a');
    const sections = Array.from(tocLinks).map(link => {
        const targetId = link.getAttribute('href').substring(1);
        return document.getElementById(targetId);
    }).filter(el => el !== null);

    if (tocLinks.length > 0 && sections.length > 0) {
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -60% 0px',
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    tocLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href').substring(1) === id) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }, observerOptions);

        sections.forEach(section => observer.observe(section));
    }
});
