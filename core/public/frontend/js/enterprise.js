document.addEventListener("DOMContentLoaded", () => {
    // Navbar Scroll Effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // Intersection Observer for Reveal Animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const revealElements = document.querySelectorAll('.reveal');
    revealElements.forEach(el => observer.observe(el));

    // Simple Code Tab Switcher
    const codeTabs = document.querySelectorAll('.code-tab');
    if (codeTabs.length > 0) {
        codeTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetId = tab.getAttribute('data-target');
                const container = tab.closest('.code-window');
                
                // Remove active from all tabs
                container.querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
                // Add active to clicked
                tab.classList.add('active');
                
                // Hide all contents
                container.querySelectorAll('.code-content').forEach(c => c.style.display = 'none');
                // Show target content
                container.querySelector('#' + targetId).style.display = 'block';
            });
        });
    }

    // Real-time Latency Simulation
    const latencyEl = document.getElementById('latency-value');
    if (latencyEl) {
        setInterval(() => {
            const base = 42;
            const fluctuation = Math.floor(Math.random() * 9) - 4; // -4 to +4
            latencyEl.innerText = base + fluctuation;
        }, 1500);
    }
});
