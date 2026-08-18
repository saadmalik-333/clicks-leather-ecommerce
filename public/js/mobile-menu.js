// Mobile Menu Functionality
(function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuBackdrop = document.getElementById('mobileMenuBackdrop');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    
    if (!hamburgerBtn || !mobileMenu || !mobileMenuBackdrop || !mobileMenuClose) {
        return;
    }
    
    function openMenu() {
        mobileMenu.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
    }
    
    hamburgerBtn.addEventListener('click', openMenu);
    mobileMenuBackdrop.addEventListener('click', closeMenu);
    mobileMenuClose.addEventListener('click', closeMenu);
    
    // Close menu when clicking a nav link
    document.querySelectorAll('.mobile-nav-categories a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
            closeMenu();
        }
    });
})();
