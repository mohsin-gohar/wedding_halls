import './bootstrap';

/**
 * Wedding_Hall Luxury Interactive Engine
 * 1. IntersectionObserver for Butter-Smooth Scroll Reveal Animations
 * 2. GPU-Accelerated 60fps Parallax Background Scrolling Engine
 * 3. Top Scroll Reading Progress Bar
 */

document.addEventListener('DOMContentLoaded', () => {
    initScrollEngine();
});

// Re-initialize when Livewire navigates or updates DOM
document.addEventListener('livewire:navigated', () => {
    initScrollEngine();
});

function initScrollEngine() {
    initScrollProgress();
    initScrollReveal();
    initParallaxEngine();
}

/**
 * 1. Top Reading Scroll Progress Bar
 */
function initScrollProgress() {
    let progressBar = document.querySelector('.scroll-progress-bar');
    if (!progressBar) {
        progressBar = document.createElement('div');
        progressBar.className = 'scroll-progress-bar';
        document.body.prepend(progressBar);
    }

    const updateProgress = () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const progress = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
        progressBar.style.width = `${progress}%`;
    };

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
}

/**
 * 2. High-Performance IntersectionObserver for Scroll Reveals
 */
function initScrollReveal() {
    const revealElements = document.querySelectorAll(
        '.reveal-on-scroll, .reveal-slide-left, .reveal-slide-right, .reveal-zoom'
    );

    if (!('IntersectionObserver' in window)) {
        // Fallback for older browsers
        revealElements.forEach(el => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                // Optional: unobserve after reveal so it stays visible
                obs.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach(el => observer.observe(el));
}

/**
 * 3. GPU-Accelerated 60fps Parallax Background Scrolling Engine
 */
function initParallaxEngine() {
    const parallaxElements = document.querySelectorAll('[data-parallax], .parallax-bg');
    if (parallaxElements.length === 0) return;

    let ticking = false;

    const onScroll = () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                updateParallax(parallaxElements);
                ticking = false;
            });
            ticking = true;
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    updateParallax(parallaxElements);
}

function updateParallax(elements) {
    const windowHeight = window.innerHeight;

    elements.forEach(el => {
        const parent = el.closest('.parallax-window') || el.parentElement;
        if (!parent) return;

        const rect = parent.getBoundingClientRect();

        // Only compute if within or near viewport
        if (rect.top <= windowHeight && rect.bottom >= 0) {
            const speed = parseFloat(el.getAttribute('data-parallax')) || 0.25;
            // Center-relative offset
            const offsetFromCenter = (rect.top + rect.height / 2) - (windowHeight / 2);
            const translateY = offsetFromCenter * speed;

            el.style.transform = `translate3d(0, ${translateY.toFixed(2)}px, 0)`;
        }
    });
}
