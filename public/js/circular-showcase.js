// Circular category showcase auto-scroll with user interaction pause
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

    let autoScrollInterval;
    let pauseTimeout;
    let isAnimating = false;
    const scrollDelay = 3000; // 3 seconds
    const pauseAfterInteraction = 4000; // 4 seconds after user interaction

    // Update progress bar (continuous 0-100% based on scroll position)
    function updateProgressBar(scrollLeft, maxScroll) {
        if (!progressBar) return;
        const progress = maxScroll > 0 ? (scrollLeft / maxScroll) * 100 : 0;
        progressBar.style.width = progress + '%';
        progressBar.style.left = '0';
    }

    // Auto-scroll function with per-item scrolling
    function autoScroll() {
        const maxScroll = track.scrollWidth - showcase.offsetWidth;
        const currentScroll = showcase.scrollLeft;
        const perCircleScroll = 272; // circleWidth (240px) + gap (32px)

        if (maxScroll <= 0) {
            return;
        }

        if (currentScroll >= maxScroll - 10) {
            // At end - instant jump to start (no animation)
            showcase.scrollLeft = 0;
            updateProgressBar(0, maxScroll);
        } else {
            // Scroll forward by one circle
            const target = Math.min(currentScroll + perCircleScroll, maxScroll);
            smoothScrollTo(showcase, target, 300);
        }
    }

    // Custom smooth scroll with configurable duration
    function smoothScrollTo(element, target, duration) {
        isAnimating = true;
        const start = element.scrollLeft;
        const change = target - start;
        const startTime = performance.now();
        const maxScroll = track.scrollWidth - showcase.offsetWidth;

        function animate(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Ease out cubic for snappy feel
            const easeOut = 1 - Math.pow(1 - progress, 3);
            
            element.scrollLeft = start + change * easeOut;
            
            // Update progress bar during animation (no layout reads)
            if (progressBar) {
                const currentScroll = start + change * easeOut;
                updateProgressBar(currentScroll, maxScroll);
            }
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                isAnimating = false;
            }
        }

        requestAnimationFrame(animate);
    }

    // Start auto-scroll
    function startAutoScroll() {
        autoScrollInterval = setInterval(autoScroll, scrollDelay);
    }

    // Stop auto-scroll
    function stopAutoScroll() {
        clearInterval(autoScrollInterval);
    }

    // Pause auto-scroll on user interaction
    function pauseAutoScroll() {
        stopAutoScroll();
        clearTimeout(pauseTimeout);
        pauseTimeout = setTimeout(startAutoScroll, pauseAfterInteraction);
    }

    // Event listeners for user interaction
    showcase.addEventListener('scroll', function() {
        if (!isAnimating) {
            const scrollLeft = showcase.scrollLeft;
            const maxScroll = track.scrollWidth - showcase.offsetWidth;
            updateProgressBar(scrollLeft, maxScroll);
        }
    });
    showcase.addEventListener('touchstart', pauseAutoScroll);
    showcase.addEventListener('touchmove', pauseAutoScroll);
    showcase.addEventListener('touchend', pauseAutoScroll);
    showcase.addEventListener('mousedown', pauseAutoScroll);
    showcase.addEventListener('mouseup', pauseAutoScroll);

    // Initial progress bar update
    const initialScrollLeft = showcase.scrollLeft;
    const initialMaxScroll = track.scrollWidth - showcase.offsetWidth;
    updateProgressBar(initialScrollLeft, initialMaxScroll);

    // Start auto-scroll initially
    startAutoScroll();
})();
