import { adminList } from './common';
import { mix } from '../../ui/mix';

/** Port of pages/admin/UserCVs.jsx */
export default function register(Alpine) {
    Alpine.data('adminUserCvs', (userId) =>
        mix(adminList({ endpoint: 'admin/resumes', errorMessage: 'Failed to load CVs' }), {
            userId,
            search: '',
            userInfo: null,
            selected: null,
            init() {
                this.$watch('search', () => this.load(1));
                this.load(1);
            },
            params() {
                return { user_id: this.userId, ...(this.search && { search: this.search }) };
            },
            afterLoad() {
                if (this.items.length > 0 && this.items[0].user) this.userInfo = this.items[0].user;
            },
            async view(id) {
                try {
                    const { data } = await window.api.get(`admin/resumes/${id}`);
                    if (data.status) {
                        this.selected = data.data;
                        window.scrollTo(0, 0);
                    } else {
                        window.toast.error('Failed to load resume details');
                    }
                } catch {
                    window.toast.error('Failed to load resume details');
                }
            },
        }),
    );
}
