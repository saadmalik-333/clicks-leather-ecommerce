/**
 * Clicks Leather — Header Scroll Effect
 * Multi-stage header scroll effect with hysteresis to prevent flickering
 */

// Multi-Stage Header Scroll Effect with Hysteresis
let isTicking = false;
let currentStage = 1; // Track current stage to prevent flickering

window.addEventListener('scroll', function() {
    if (!isTicking) {
        window.requestAnimationFrame(function() {
            const header = document.getElementById('site-header');
            const currentScrollY = window.scrollY;
            
            // Hysteresis thresholds (different activate/deactivate points)
            const stage2Activate = 40;  // Activate Stage 2 when scrolling down past 40px
            const stage2Deactivate = 20; // Deactivate Stage 2 when scrolling up below 20px
            const stage3Activate = 160; // Activate Stage 3 when scrolling down past 160px
            const stage3Deactivate = 140; // Deactivate Stage 3 when scrolling up below 140px
            
            // Base state (Stage 1)
            if (currentStage === 1 && currentScrollY < stage2Activate) {
                header.style.boxShadow = 'none';
                header.classList.remove('stage-2', 'stage-3');
            }
            // Transition to Stage 2
            else if (currentStage === 1 && currentScrollY >= stage2Activate) {
                header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                header.classList.add('stage-2');
                header.classList.remove('stage-3');
                currentStage = 2;
            }
            // Stay in Stage 2 (within hysteresis buffer)
            else if (currentStage === 2 && currentScrollY >= stage2Deactivate && currentScrollY < stage3Activate) {
                header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                header.classList.add('stage-2');
                header.classList.remove('stage-3');
            }
            // Transition back to Stage 1
            else if (currentStage === 2 && currentScrollY < stage2Deactivate) {
                header.style.boxShadow = 'none';
                header.classList.remove('stage-2', 'stage-3');
                currentStage = 1;
            }
            // Transition to Stage 3
            else if ((currentStage === 2 || currentStage === 1) && currentScrollY >= stage3Activate) {
                header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                header.classList.add('stage-2', 'stage-3');
                currentStage = 3;
            }
            // Stay in Stage 3
            else if (currentStage === 3 && currentScrollY >= stage3Deactivate) {
                header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                header.classList.add('stage-2', 'stage-3');
            }
            // Transition back to Stage 2
            else if (currentStage === 3 && currentScrollY < stage3Deactivate) {
                header.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
                header.classList.add('stage-2');
                header.classList.remove('stage-3');
                currentStage = 2;
            }
            
            isTicking = false;
        });
        isTicking = true;
    }
});
