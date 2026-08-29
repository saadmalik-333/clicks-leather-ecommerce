// Filter Drawer Toggle
(function() {
    const filtersSidebar = document.querySelector('.filters-sidebar');
    const filterDrawerOverlay = document.getElementById('filter-drawer-overlay');
    const filterToggleBtn = document.getElementById('filters-toggle-btn');
    const siteHeader = document.getElementById('site-header');
    
    if (!filtersSidebar || !filterDrawerOverlay || !filterToggleBtn || !siteHeader) {
        return;
    }
    
    function openDrawer() {
        // Measure header's current height
        const headerHeight = siteHeader.offsetHeight;
        
        // Set dynamic top and height for filter drawer
        filtersSidebar.style.top = headerHeight + 'px';
        filtersSidebar.style.height = 'calc(100% - ' + headerHeight + 'px)';
        
        filtersSidebar.classList.add('active');
        filterDrawerOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeDrawer() {
        filtersSidebar.classList.remove('active');
        filterDrawerOverlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Clear inline styles to revert to CSS default
        filtersSidebar.style.top = '';
        filtersSidebar.style.height = '';
    }
    
    filterToggleBtn.addEventListener('click', openDrawer);
    filterDrawerOverlay.addEventListener('click', closeDrawer);
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && filtersSidebar.classList.contains('active')) {
            closeDrawer();
        }
    });
})();
