import { adminList } from './common';
import { mix } from '../../ui/mix';

/** Port of pages/admin/ReviewsManagement.jsx */
export default function register(Alpine) {
    Alpine.data('adminReviews', () =>
        mix(adminList({ endpoint: 'admin/reviews', errorMessage: 'Failed to load reviews' }), {
            search: '',
            deletingId: null,
            togglingId: null,
            init() {
                this.$watch('search', () => this.load(1));
                this.load(1);
            },
            params() {
                return { search: this.search };
            },
            shortDate(d) {
                return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            },
            async toggle(review) {
                this.togglingId = review.id;
                try {
                    const { data } = await window.api.patch(`admin/reviews/${review.id}/toggle-public`);
                    if (data.status) {
                        review.is_public = !review.is_public;
                        window.toast.success('Review visibility updated');
                    }
                } catch {
                    window.toast.error('Failed to update visibility');
                } finally {
                    this.togglingId = null;
                }
            },
            async remove(review) {
                if (!window.confirm('Are you sure you want to delete this review? This action cannot be undone.')) return;
                this.deletingId = review.id;
                try {
                    const { data } = await window.api.delete(`admin/reviews/${review.id}`);
                    if (data.status) {
                        this.items = this.items.filter((r) => r.id !== review.id);
                        this.pagination.total -= 1;
                        window.toast.success('Review deleted successfully');
                    }
                } catch {
                    window.toast.error('Failed to delete review');
                } finally {
                    this.deletingId = null;
                }
            },
        }),
    );
}
