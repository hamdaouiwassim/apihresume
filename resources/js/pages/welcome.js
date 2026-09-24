/**
 * Home page interactions (port of pages/welcome.jsx, CountUpNumber.jsx, ReviewsCarousel.jsx).
 */
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function formatStatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M+';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K+';
    return String(num);
}

function observeOnce(el, options, callback) {
    const obs = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
            callback();
            obs.disconnect();
        }
    }, options);
    obs.observe(el);
}

export default function register(Alpine) {
    /** Stats block: numbers count up from 0 once visible. */
    Alpine.data('statsCounter', (targets = {}) => ({
        display: { ...targets },
        init() {
            if (reducedMotion()) return;
            Object.keys(targets).forEach((k) => (this.display[k] = 0));
            observeOnce(this.$el, { rootMargin: '0px 0px -10% 0px', threshold: 0.2 }, () => this.animate());
        },
        animate() {
            const duration = 2200;
            const ease = (t) => 1 - (1 - t) ** 3;
            let start = null;
            const tick = (ts) => {
                if (start === null) start = ts;
                const p = Math.min((ts - start) / duration, 1);
                Object.entries(targets).forEach(([k, v]) => {
                    this.display[k] = p < 1 ? Math.round(v * ease(p)) : v;
                });
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        },
        fmt(k) {
            return formatStatNumber(this.display[k] ?? 0);
        },
    }));

    /** Before/after "Enhance with AI" simulation. */
    Alpine.data('transformationDemo', (count = 2) => ({
        visible: false,
        phase: {},
        enhancingIdx: null,
        timers: [],
        init() {
            if (reducedMotion()) {
                this.visible = true;
                for (let i = 0; i < count; i += 1) this.phase[i] = 'done';
                return;
            }
            observeOnce(this.$el, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 }, () => (this.visible = true));
        },
        destroy() {
            this.timers.forEach((id) => clearTimeout(id));
        },
        phaseOf(idx) {
            return this.phase[idx] ?? 'idle';
        },
        enhance(idx) {
            if (this.phaseOf(idx) !== 'idle' || this.enhancingIdx !== null) return;
            this.timers.forEach((id) => clearTimeout(id));
            this.timers = [];
            this.enhancingIdx = idx;
            this.timers.push(
                setTimeout(() => {
                    this.enhancingIdx = null;
                    this.phase = { ...this.phase, [idx]: 'enhancing' };
                    this.timers.push(
                        setTimeout(() => {
                            this.phase = { ...this.phase, [idx]: 'done' };
                        }, 1300),
                    );
                }, 520),
            );
        },
    }));

    /** Reviews carousel with autoplay, arrows, dots and swipe. */
    Alpine.data('reviewsCarousel', (count = 0) => ({
        count,
        index: 0,
        perSlide: 3,
        paused: false,
        touchStart: null,
        touchEnd: null,
        timer: null,
        get totalSlides() {
            return Math.ceil(this.count / this.perSlide);
        },
        init() {
            this.updatePerSlide();
            window.addEventListener('resize', () => this.updatePerSlide());
            this.timer = setInterval(() => {
                if (this.totalSlides > 1 && !this.paused) this.index = (this.index + 1) % this.totalSlides;
            }, 5000);
        },
        destroy() {
            clearInterval(this.timer);
        },
        updatePerSlide() {
            const w = window.innerWidth;
            this.perSlide = w < 640 ? 1 : w < 1024 ? 2 : 3;
            if (this.index >= this.totalSlides) this.index = 0;
        },
        pauseBriefly() {
            this.paused = true;
            setTimeout(() => (this.paused = false), 3000);
        },
        next() {
            this.index = (this.index + 1) % this.totalSlides;
        },
        prev() {
            this.index = (this.index - 1 + this.totalSlides) % this.totalSlides;
        },
        go(i) {
            this.index = i;
            this.pauseBriefly();
        },
        onTouchStart(e) {
            this.touchEnd = null;
            this.touchStart = e.targetTouches[0].clientX;
            this.paused = true;
        },
        onTouchMove(e) {
            this.touchEnd = e.targetTouches[0].clientX;
        },
        onTouchEnd() {
            if (!this.touchStart || !this.touchEnd) return;
            const d = this.touchStart - this.touchEnd;
            if (d > 50) this.next();
            else if (d < -50) this.prev();
            setTimeout(() => (this.paused = false), 3000);
        },
        get trackStyle() {
            return `transform: translateX(-${this.index * (100 / this.perSlide)}%); will-change: transform;`;
        },
        get cardClass() {
            return this.perSlide === 1 ? 'w-full' : this.perSlide === 2 ? 'w-1/2' : 'w-1/3';
        },
    }));
}
