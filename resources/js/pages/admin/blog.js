import { adminList } from './common';
import { mix } from '../../ui/mix';

/** Port of pages/admin/Blog.jsx */
export default function register(Alpine) {
    Alpine.data('adminBlog', (strings = {}) =>
        mix(adminList({ endpoint: 'admin/blog', errorMessage: strings.fetchError || 'Failed to load blog posts' }), {
            search: '',
            status: 'all',
            init() {
                this.$watch('search', () => this.load(1));
                this.$watch('status', () => this.load(1));
                this.load(1);
            },
            params() {
                return { ...(this.search && { search: this.search }), ...(this.status !== 'all' && { status: this.status }) };
            },
            shortDate(d) {
                if (!d) return '';
                return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            },
            async remove(post) {
                const ok = await window.confirmDialog({
                    title: strings.deleteConfirmTitle || 'Delete Blog Post',
                    message: strings.deleteConfirmMessage || 'Are you sure you want to delete this blog post? This action cannot be undone.',
                    confirmText: strings.delete || 'Delete',
                    cancelText: strings.cancel || 'Cancel',
                });
                if (!ok) return;
                try {
                    const { data } = await window.api.delete(`admin/blog/${post.id}`);
                    if (data.status) {
                        window.toast.success(strings.deleteSuccess || 'Blog post deleted successfully');
                        this.reload();
                    }
                } catch {
                    window.toast.error(strings.deleteError || 'Failed to delete blog post');
                }
            },
        }),
    );
}
