/**
 * Shared helpers for admin list pages (API-driven, like the former React admin pages).
 */
export const PER_PAGE_OPTIONS = [10, 25, 50, 100];
export const DEFAULT_PER_PAGE = 25;

export function adminDate(value, withTime = false) {
    if (!value) return withTime ? 'Never' : 'N/A';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return 'N/A';
    const locale = window.__APP__?.locale === 'fr' ? 'fr-FR' : 'en-US';
    const opts = { year: 'numeric', month: 'short', day: 'numeric' };
    if (withTime) Object.assign(opts, { hour: '2-digit', minute: '2-digit' });
    return d.toLocaleDateString(locale, opts);
}

export function timeAgo(value) {
    if (!value) return 'Never';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return 'N/A';
    const ms = Date.now() - d;
    const mins = Math.floor(ms / 60000);
    const hours = Math.floor(ms / 3600000);
    const days = Math.floor(ms / 86400000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return adminDate(value, true);
}

/**
 * Paginated list state. `params()` builds query params from the component's filters.
 *   ...adminList({ endpoint: 'admin/users', label: 'users' })
 */
export function adminList({ endpoint, perPage = DEFAULT_PER_PAGE, errorMessage = 'Failed to load data' } = {}) {
    return {
        endpoint,
        items: [],
        loading: true,
        loaded: false,
        perPage,
        pagination: { current_page: 1, last_page: 1, total: 0 },
        params() {
            return {};
        },
        async load(page = 1) {
            this.loading = true;
            try {
                const { data } = await window.api.get(this.endpoint, { params: { per_page: this.perPage, page, ...this.params() } });
                if (data.status === false) {
                    window.toast.error(errorMessage);
                    return;
                }
                const payload = data.data ?? data;
                const list = Array.isArray(payload) ? payload : payload.data || [];
                this.items = list;
                this.pagination =
                    payload.current_page !== undefined
                        ? { current_page: payload.current_page || 1, last_page: payload.last_page || 1, total: payload.total ?? list.length }
                        : { current_page: 1, last_page: 1, total: list.length };
                this.afterLoad?.(data);
            } catch (error) {
                if (error.response?.status !== 401) window.toast.error(errorMessage);
            } finally {
                this.loading = false;
                this.loaded = true;
            }
        },
        reload() {
            return this.load(this.pagination.current_page);
        },
        setPerPage(size) {
            if (size === this.perPage) return;
            this.perPage = size;
            this.load(1);
        },
        get fromRow() {
            return this.pagination.total === 0 ? 0 : (this.pagination.current_page - 1) * this.perPage + 1;
        },
        get toRow() {
            return this.pagination.total === 0 ? 0 : Math.min(this.pagination.current_page * this.perPage, this.pagination.total);
        },
        adminDate,
        timeAgo,
    };
}

export const OUTBOUND_TYPES = {
    admin_custom: 'Custom message',
    resume_incomplete_reminder: 'Resume reminder',
    email_verification_reminder: 'Verification reminder',
    new_features_announcement: 'New features',
};
export const OUTBOUND_STATUS = {
    queued: { label: 'Queued', className: 'bg-amber-100 text-amber-900' },
    processing: { label: 'Processing', className: 'bg-blue-100 text-blue-900' },
    sent: { label: 'Sent', className: 'bg-emerald-100 text-emerald-900' },
    failed: { label: 'Failed', className: 'bg-red-100 text-red-900' },
    skipped: { label: 'Skipped', className: 'bg-gray-100 text-gray-700' },
};
export const outboundType = (type) => OUTBOUND_TYPES[type] || type || '—';
export const outboundStatus = (status) => OUTBOUND_STATUS[status] || { label: status, className: 'bg-gray-100 text-gray-700' };

export default function register() {
    window.outboundType = outboundType;
    window.outboundStatus = outboundStatus;
    window.adminList = adminList;
    window.adminDate = adminDate;
    window.timeAgo = timeAgo;
    window.PER_PAGE_OPTIONS = PER_PAGE_OPTIONS;
}
