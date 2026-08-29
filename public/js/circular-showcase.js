// Circular category showcase - manual scroll only
(function() {
    const showcase = document.getElementById('circularShowcase');
    if (!showcase) {
        return;
    }

    const track = showcase.querySelector('.circular-showcase-track');
    const progressBar = document.getElementById('circularShowcaseProgress');
    if (!track) {
        return;
    }

    // Update progress bar (continuous 0-100% based on scroll position)
    function updateProgressBar(scrollLeft, maxScroll) {
        if (!progressBar) return;
        const progress = maxScroll > 0 ? (scrollLeft / maxScroll) * 100 : 0;
        progressBar.style.width = progress + '%';
        progressBar.style.left = '0';
    }

    // Event listener for manual scroll
    showcase.addEventListener('scroll', function() {
        const scrollLeft = showcase.scrollLeft;
        const maxScroll = track.scrollWidth - showcase.offsetWidth;
        updateProgressBar(scrollLeft, maxScroll);
    });

    // Initial progress bar update
    const initialScrollLeft = showcase.scrollLeft;
    const initialMaxScroll = track.scrollWidth - showcase.offsetWidth;
    updateProgressBar(initialScrollLeft, initialMaxScroll);
})();
