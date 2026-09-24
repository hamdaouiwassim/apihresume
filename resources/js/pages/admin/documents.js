import { adminList } from './common';
import { mix } from '../../ui/mix';

/**
 * Master/detail list shared by admin CoverLettersManagement.jsx and WorkCertificatesManagement.jsx.
 * Search runs on button/Enter (not on typing), like the React pages.
 */
export default function register(Alpine) {
    Alpine.data('adminDocuments', ({ endpoint, messages }) =>
        mix(adminList({ endpoint, errorMessage: messages.loadError }), {
            search: '',
            selected: null,
            init() {
                this.load(1);
            },
            params() {
                return { search: this.search };
            },
            async view(id) {
                try {
                    const { data } = await window.api.get(`${endpoint}/${id}`);
                    if (data?.status) this.selected = data.data;
                } catch {
                    window.toast.error(messages.viewError);
                }
            },
            async remove(item) {
                const ok = await window.confirmDialog({
                    title: messages.confirmTitle,
                    message: messages.confirmMessage,
                    itemName: item.title,
                    confirmText: 'Delete',
                    cancelText: 'Cancel',
                });
                if (!ok) return;
                try {
                    await window.api.delete(`${endpoint}/${item.id}`);
                    window.toast.success(messages.deleted);
                    this.selected = null;
                    this.reload();
                } catch {
                    window.toast.error(messages.deleteError);
                }
            },
            shortDate(d) {
                if (!d) return '—';
                const date = new Date(d);
                return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            },
        }),
    );
}
