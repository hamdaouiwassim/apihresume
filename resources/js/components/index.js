import guestNav from './guestNav';
import newsletter from './newsletter';
import collabNotifications from './collabNotifications';
import { registerAi } from './ai';

/**
 * Alpine components shared across layouts. Page-specific components live in ../pages
 * and register themselves through the same function list.
 */
const pageModules = import.meta.glob('../pages/**/*.js', { eager: true });

export function registerComponents(Alpine) {
    registerAi(Alpine);
    Alpine.data('guestNav', guestNav);
    Alpine.data('newsletter', newsletter);
    Alpine.data('collabNotifications', collabNotifications);

    Object.values(pageModules).forEach((mod) => {
        if (typeof mod.default === 'function') {
            mod.default(Alpine);
        }
    });
}
