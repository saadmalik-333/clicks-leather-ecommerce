// Auto-fade flash messages after 6.5 seconds
(function() {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function(flashMessage) {
        setTimeout(function() {
            flashMessage.classList.add('fade-out');
            setTimeout(function() {
                flashMessage.style.display = 'none';
            }, 500);
        }, 6500);
    });
})();
