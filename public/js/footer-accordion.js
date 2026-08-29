// Footer Accordion JavaScript
(function() {
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const accordion = this.closest('.footer-accordion');
            accordion.classList.toggle('active');
        });
    });
})();
