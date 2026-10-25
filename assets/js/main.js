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
});
