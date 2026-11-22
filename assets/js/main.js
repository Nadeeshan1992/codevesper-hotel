/**
 * Main Application JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile/Tablet Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');

    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function() {
            appSidebar.classList.toggle('show');
        });

        // Close sidebar if clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                if (!appSidebar.contains(e.target) && !sidebarToggle.contains(e.target) && appSidebar.classList.contains('show')) {
                    appSidebar.classList.remove('show');
                }
            }
        });
    }

    // Initialize all tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize default DataTables if present
    if (window.jQuery && $.fn.DataTable) {
        $('.datatable-init').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records..."
            }
        });
    }

    // Live Real-Time Date & Clock Ticker
    function updateLiveDateTime() {
        const now = new Date();
        const dateEls = document.querySelectorAll('.live-current-date');
        const clockEls = document.querySelectorAll('.live-current-time');
        if (!dateEls.length && !clockEls.length) return;

        const dateOptions = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
        const dateStr = now.toLocaleDateString('en-US', dateOptions);

        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // 0 becomes 12
        const timeStr = `${String(hours).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;

        dateEls.forEach(el => el.textContent = dateStr);
        clockEls.forEach(el => el.textContent = timeStr);
    }
    updateLiveDateTime();
    setInterval(updateLiveDateTime, 1000);
});
