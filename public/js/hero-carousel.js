// Hero Carousel - Reuses product carousel logic
(function() {
    const carousel = document.querySelector('.hero-carousel');
    if (!carousel) return;
    
    const track = carousel.querySelector('.hero-carousel-track');
    const slides = track.querySelectorAll('.hero-slide');
    const dotsContainer = document.querySelector('.hero-carousel-dots');
    const dots = dotsContainer ? dotsContainer.querySelectorAll('.hero-dot') : [];
    
    if (slides.length === 0) return;
    
    let currentIndex = 0;
    let startX = 0;
    let isDragging = false;
    let autoAdvanceInterval = null;
    let isPaused = false;
    
    function updateCarousel() {
        const isDesktop = window.innerWidth >= 1024;
        
        if (isDesktop) {
            // Desktop: show all 3, no transform
            track.style.transform = 'translateX(0)';
        } else {
            // Tablet/Mobile: show 1 at a time, translate by 100% increments
            track.style.transform = `translateX(-${currentIndex * 100}%)`;
        }
        
        // Update active dot
        updateDots();
    }
    
    function updateDots() {
        dots.forEach((dot, index) => {
            if (index === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }
    
    function startAutoAdvance() {
        if (autoAdvanceInterval) clearInterval(autoAdvanceInterval);
        autoAdvanceInterval = setInterval(function() {
            if (!isPaused) {
                currentIndex = (currentIndex + 1) % slides.length;
                updateCarousel();
            }
        }, 6000);
    }
    
    function pauseAutoAdvance() {
        isPaused = true;
        if (autoAdvanceInterval) {
            clearInterval(autoAdvanceInterval);
            autoAdvanceInterval = null;
        }
    }
    
    function resumeAutoAdvance() {
        isPaused = false;
        startAutoAdvance();
    }
    
    // Dot click handlers
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            currentIndex = index;
            updateCarousel();
            pauseAutoAdvance();
            setTimeout(resumeAutoAdvance, 2000);
        });
    });
    
    // Touch events
    carousel.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        isDragging = true;
        pauseAutoAdvance();
    }, { passive: true });
    
    carousel.addEventListener('touchmove', function(e) {
        if (!isDragging) return;
        const diff = startX - e.touches[0].clientX;
        if (Math.abs(diff) > 50) {
            if (diff > 0 && currentIndex < slides.length - 1) {
                currentIndex++;
            } else if (diff < 0 && currentIndex > 0) {
                currentIndex--;
            }
            updateCarousel();
            isDragging = false;
        }
    }, { passive: true });
    
    carousel.addEventListener('touchend', function() {
        isDragging = false;
        setTimeout(resumeAutoAdvance, 2000);
    }, { passive: true });
    
    // Wheel event for trackpad (exact copy from product carousel)
    let isLocked = false;
    let lockStartTime = null;
    let hasSettled = false;
    let smallDeltaStreak = 0;
    let gestureEndTimer = null;
    
    carousel.addEventListener('wheel', function(e) {
        // Only handle horizontal gestures (trackpad two-finger swipe)
        if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
            e.preventDefault();
            pauseAutoAdvance();
            
            if (isLocked) {
                // Track consecutive small-delta readings to detect genuine settling
                if (Math.abs(e.deltaX) <= 3) {
                    smallDeltaStreak++;
                    if (smallDeltaStreak >= 3 && !hasSettled) {
                        hasSettled = true;
                    }
                } else {
                    smallDeltaStreak = 0;
                }
                
                if (hasSettled && Math.abs(e.deltaX) >= 15) {
                    isLocked = false;
                    lockStartTime = null;
                    hasSettled = false;
                    smallDeltaStreak = 0;
                    clearTimeout(gestureEndTimer);
                } else if (lockStartTime && Date.now() - lockStartTime > 3000) {
                    isLocked = false;
                    lockStartTime = null;
                    hasSettled = false;
                    smallDeltaStreak = 0;
                    clearTimeout(gestureEndTimer);
                } else {
                    clearTimeout(gestureEndTimer);
                    gestureEndTimer = setTimeout(function() {
                        isLocked = false;
                        lockStartTime = null;
                        hasSettled = false;
                        smallDeltaStreak = 0;
                    }, 150);
                    return;
                }
            }
            
            if (Math.abs(e.deltaX) < 15) {
                return;
            }
            
            isLocked = true;
            lockStartTime = Date.now();
            hasSettled = false;
            smallDeltaStreak = 0;
            
            if (e.deltaX > 0 && currentIndex < slides.length - 1) {
                currentIndex++;
            } else if (e.deltaX < 0 && currentIndex > 0) {
                currentIndex--;
            }
            updateCarousel();
            
            gestureEndTimer = setTimeout(function() {
                isLocked = false;
                lockStartTime = null;
                hasSettled = false;
                smallDeltaStreak = 0;
                setTimeout(resumeAutoAdvance, 2000);
            }, 150);
        }
    }, { passive: false });
    
    // Recalculate on resize
    window.addEventListener('resize', updateCarousel);
    
    // Initial setup
    updateCarousel();
    startAutoAdvance();
})();
