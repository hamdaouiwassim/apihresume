import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

import { registerToasts } from './ui/toast';
import { registerConfirm } from './ui/confirm';
import { registerComponents } from './components';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(focus);

/**
 * $swap($el, condition, 'classes when true', 'classes when false')
 * Use with x-effect when the server already rendered one of the class sets
 * (Alpine's :class never removes server-rendered classes).
 */
Alpine.magic('swap', () => (el, condition, whenTrue, whenFalse) => {
    const on = (condition ? whenTrue : whenFalse).split(/\s+/).filter(Boolean);
    const off = (condition ? whenFalse : whenTrue).split(/\s+/).filter(Boolean);
    el.classList.remove(...off);
    el.classList.add(...on);
});

registerToasts(Alpine);
registerConfirm(Alpine);
registerComponents(Alpine);

Alpine.start();
