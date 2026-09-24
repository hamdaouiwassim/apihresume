import { adminList } from './common';
import { newFeaturesForm } from './userDetails';
import { mix } from '../../ui/mix';

/** Port of pages/admin/AdminEmails.jsx */
export default function register(Alpine) {
    Alpine.data('adminEmails', () =>
        mix(
            adminList({ endpoint: 'admin/outbound-emails', errorMessage: 'Failed to load outbound emails' }),
            newFeaturesForm({
                async sendBulk(payload) {
                    const res = await window.api.post('admin/outbound-emails/new-features', payload);
                    if (!res.data?.status) throw new Error(res.data?.message || 'Bulk send failed');
                    window.toast.success(res.data.message || 'Announcements queued');
                    this.refresh();
                },
            }),
            {
                summary: null,
                status: '',
                type: '',
                search: '',
                bulkLoading: null,
                init() {
                    ['status', 'type', 'search'].forEach((k) => this.$watch(k, () => this.refresh(1)));
                    this.refresh(1);
                },
                params() {
                    return {
                        ...(this.status && { status: this.status }),
                        ...(this.type && { type: this.type }),
                        ...(this.search && { search: this.search }),
                    };
                },
                async refresh(page = this.pagination.current_page) {
                    try {
                        const { data } = await window.api.get('admin/outbound-emails/summary');
                        if (data?.status) this.summary = data.data;
                    } catch {
                        window.toast.error('Failed to load outbound emails');
                    }
                    this.load(page);
                },
                fullDate(d) {
                    if (!d) return '—';
                    const date = new Date(d);
                    return Number.isNaN(date.getTime()) ? '—' : date.toLocaleString();
                },
                badge(status) {
                    return window.outboundStatus(status);
                },
                typeLabel(type) {
                    return window.outboundType(type);
                },
                async bulk(type, filter) {
                    const key = `${type}-${filter}`;
                    if (!window.confirm(`Queue ${filter.replace('_', ' ')} emails? This may send many messages.`)) return;
                    this.bulkLoading = key;
                    try {
                        const res = await window.api.post('admin/outbound-emails/bulk', { type, filter });
                        if (res.data?.status) {
                            window.toast.success(res.data.message || 'Bulk send queued');
                            this.refresh();
                        }
                    } catch (e) {
                        window.toast.error(e.response?.data?.message || 'Bulk send failed');
                    } finally {
                        this.bulkLoading = null;
                    }
                },
            },
        ),
    );
}
