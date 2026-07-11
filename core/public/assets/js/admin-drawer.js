function openAdminDrawer(drawerId, data) {
    // Populate UI fields generically based on data keys matching ID names
    for (const [key, value] of Object.entries(data)) {
        const el = document.getElementById(key);
        if (el) {
            if (key.includes('Amount') || key.includes('Avail')) {
                // If it's the main amount, handle styling
                if (key === 'drawerAmount' && value.type) {
                    el.textContent = value.text;
                    el.className = value.type === '+' ? 'mb-0 amount-in' : 'mb-0 amount-out';
                } else if (value.text) {
                    el.textContent = value.text;
                } else {
                    el.textContent = value;
                }
            } else if (key === 'drawerStatus') {
                if (value.html) {
                    el.innerHTML = value.html;
                } else if (value.class) {
                    el.textContent = value.text;
                    el.className = value.class;
                } else {
                    el.textContent = value;
                }
            } else if (key === 'drawerTimelineContainer' || key === 'drawerQuickLinks') {
                el.innerHTML = value;
            } else {
                el.textContent = value;
            }
        }
    }

    // Open Offcanvas
    var myOffcanvas = new bootstrap.Offcanvas(document.getElementById(drawerId));
    myOffcanvas.show();
}
