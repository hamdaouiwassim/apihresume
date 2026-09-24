/** Port of the GuestLayout nav state: mobile menu + transparent "hero" variant until scrolled. */
export default function guestNav(variant = 'default') {
    return {
        open: false,
        scrolled: false,
        variant,
        get isHero() {
            return this.variant === 'hero' && !this.scrolled;
        },
        /**
         * Swap between two class sets. Server renders the initial set, so there is no flash,
         * and unlike :class this also removes server-rendered classes.
         */
        swap(el, condition, whenTrue, whenFalse) {
            const on = (condition ? whenTrue : whenFalse).split(/\s+/).filter(Boolean);
            const off = (condition ? whenFalse : whenTrue).split(/\s+/).filter(Boolean);
            el.classList.remove(...off);
            el.classList.add(...on);
        },
        init() {
            if (this.variant !== 'hero') return;
            const threshold = 480;
            let ticking = false;
            const update = () => {
                ticking = false;
                this.scrolled = window.scrollY > threshold;
            };
            update();
            window.addEventListener(
                'scroll',
                () => {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(update);
                },
                { passive: true },
            );
        },
    };
}
