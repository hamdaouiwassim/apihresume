/**
 * Lightweight toast store styled like sonner (richColors, bottom-center) used by the old React app.
 * Usage anywhere: window.toast.success('Saved'), window.toast.error('Oops').
 * Supports sonner's { id } option (a toast with the same id is replaced) and toast.loading().
 * Flash messages from Laravel (session success/error) are pushed on page load by the layout.
 */
let nextId = 1;

export function registerToasts(Alpine) {
    Alpine.store('toasts', {
        items: [],
        timers: {},
        push(type, message, options = {}) {
            if (!message) return undefined;
            const key = options.id ?? `t${nextId++}`;
            const duration = options.duration ?? (type === 'loading' ? Infinity : 4000);
            const existing = this.items.find((t) => t.key === key);
            if (existing) {
                existing.type = type;
                existing.message = String(message);
                existing.description = options.description ?? null;
                existing.visible = true;
            } else {
                this.items.push({ id: nextId++, key, type, message: String(message), description: options.description ?? null, visible: true });
                if (this.items.length > 5) this.items.shift();
            }
            clearTimeout(this.timers[key]);
            if (duration !== Infinity) {
                this.timers[key] = setTimeout(() => this.dismiss(key), duration);
            }
            return key;
        },
        dismiss(key) {
            if (key === undefined) {
                this.items.forEach((t) => (t.visible = false));
                setTimeout(() => (this.items = []), 200);
                return;
            }
            const item = this.items.find((t) => t.key === key);
            if (!item) return;
            item.visible = false;
            setTimeout(() => {
                this.items = this.items.filter((t) => t.key !== key);
            }, 200);
        },
    });

    const store = () => Alpine.store('toasts');
    const push = (type) => (message, options) => store().push(type, message, options);
    const toast = (message, options) => store().push('default', message, options);
    toast.success = push('success');
    toast.error = push('error');
    toast.info = push('info');
    toast.warning = push('warning');
    toast.message = push('default');
    toast.loading = push('loading');
    toast.dismiss = (key) => store().dismiss(key);

    /** Show a toast on the next page load (for actions followed by a full redirect). */
    toast.next = (type, message) => {
        try {
            const list = JSON.parse(sessionStorage.getItem('hresume_next_toasts') || '[]');
            list.push([type, message]);
            sessionStorage.setItem('hresume_next_toasts', JSON.stringify(list));
        } catch {
            // ignore storage errors
        }
    };

    window.toast = toast;

    // Toasts queued before Alpine started (flash messages, islands) or by toast.next() on the previous page.
    let carried = [];
    try {
        carried = JSON.parse(sessionStorage.getItem('hresume_next_toasts') || '[]');
        sessionStorage.removeItem('hresume_next_toasts');
    } catch {
        carried = [];
    }
    const queued = [...carried, ...(window.__toastQueue || [])];
    queued.forEach(([type, message]) => push(type)(message));
    window.__toastQueue = [];
}
