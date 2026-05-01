import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const scrollToTopButton = document.getElementById('scroll-to-top');

    if (!scrollToTopButton) {
        return;
    }

    const toggleVisibility = () => {
        const isVisible = window.scrollY > 240;

        scrollToTopButton.classList.toggle('pointer-events-none', !isVisible);
        scrollToTopButton.classList.toggle('opacity-0', !isVisible);
        scrollToTopButton.classList.toggle('translate-y-3', !isVisible);
        scrollToTopButton.classList.toggle('opacity-100', isVisible);
        scrollToTopButton.classList.toggle('translate-y-0', isVisible);
    };

    scrollToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    });

    window.addEventListener('scroll', toggleVisibility, { passive: true });
    toggleVisibility();
});
