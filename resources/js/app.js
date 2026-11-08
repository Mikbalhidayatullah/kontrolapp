import './bootstrap';
import { initializeDropdowns } from './dropdowns';
import { initializeNavbar } from './navbar';
import { initializeMobileNavbar } from './mobile-navbar';
import { initializeLrfkScroll } from './lrfk-scroll';
import { initializeBpkFunding } from './bpk-funding';

initializeDropdowns(document);
initializeBpkFunding(document);

document.addEventListener('DOMContentLoaded', () => {
    initializeNavbar(document);
    initializeMobileNavbar(document);
    initializeLrfkScroll(document);
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
