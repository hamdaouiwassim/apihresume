/**
 * Merge objects while keeping getters/setters live (object spread would evaluate getters once).
 *   Alpine.data('x', () => mix(adminList({...}), { extra: 1, get total() { ... } }))
 */
export function mix(...parts) {
    const target = {};
    parts.forEach((part) => Object.defineProperties(target, Object.getOwnPropertyDescriptors(part)));
    return target;
}
