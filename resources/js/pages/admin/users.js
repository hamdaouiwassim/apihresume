import { adminList } from './common';
import { mix } from '../../ui/mix';

/** Port of pages/admin/UsersList.jsx (recruiter columns/actions removed). */
export default function register(Alpine) {
    Alpine.data('adminUsers', () => mix(adminList({ endpoint: 'admin/users', errorMessage: 'Failed to load users' }), {
        search: '',
        role: new URLSearchParams(window.location.search).get('role') || '',
        verification: '',
        trashed: '',
        deleting: false,
        init() {
            ['search', 'role', 'verification', 'trashed'].forEach((key) => this.$watch(key, () => this.load(1)));
            this.$watch('role', (value) => {
                const url = value ? `/admin/users?role=${encodeURIComponent(value)}` : '/admin/users';
                history.replaceState(null, '', url);
            });
            this.load(1);
        },
        params() {
            return {
                ...(this.search && { search: this.search }),
                ...(this.role && { role: this.role }),
                ...(this.verification && { verification_status: this.verification }),
                ...(this.trashed && { trashed: this.trashed }),
            };
        },
        get hasFilters() {
            return Boolean(this.role || this.verification || this.trashed);
        },
        clearFilters() {
            this.role = '';
            this.verification = '';
            this.trashed = '';
        },
        async remove(user) {
            const ok = await window.confirmDialog({
                title: 'Delete User',
                message: 'Move this user to trash? Their resumes and related data are soft-deleted too. You can restore them anytime from Deleted users.',
                itemName: user.name,
                confirmText: 'Yes, Delete',
                cancelText: 'Cancel',
            });
            if (!ok) return;
            this.deleting = true;
            try {
                const res = await window.api.delete(`admin/users/${user.id}`);
                window.toast.success(res.data?.message || 'User moved to trash');
                this.reload();
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Failed to delete user');
            } finally {
                this.deleting = false;
            }
        },
        async restore(user) {
            try {
                const res = await window.api.post(`admin/users/${user.id}/restore`);
                window.toast.success(res.data?.message || 'User restored');
                this.reload();
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Failed to restore user');
            }
        },
        async update(user, data, success) {
            try {
                await window.api.put(`admin/users/${user.id}`, data);
                window.toast.success(success);
                this.reload();
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Failed to update user');
            }
        },
        toggleAdmin(user) {
            this.update(user, { is_admin: !user.is_admin }, `User ${!user.is_admin ? 'promoted to' : 'demoted from'} admin`);
        },
        togglePro(user) {
            const next = !user.is_pro;
            if (next && !user.email_verified_at) {
                window.toast.error('Only verified users can be granted Pro access');
                return;
            }
            this.update(user, { is_pro: next }, next ? 'User upgraded to Pro' : 'Pro access removed');
        },
    }));
}
