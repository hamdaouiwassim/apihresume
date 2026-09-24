/** Port of pages/resumes.jsx state: delete + resume limit tracking. */
export default function register(Alpine) {
    Alpine.data('resumesPage', ({ ownedIds = [], sharedCount = 0, limits = null, strings = {} } = {}) => ({
        ownedIds,
        sharedCount,
        limits,
        deleted: [],
        deleting: false,
        get ownedCount() {
            return this.ownedIds.filter((id) => !this.deleted.includes(id)).length;
        },
        get total() {
            return this.ownedCount + this.sharedCount;
        },
        get canCreate() {
            if (!this.limits) return true;
            if (this.limits.owned_limit == null) return Boolean(this.limits.can_create ?? true);
            return this.ownedCount < this.limits.owned_limit;
        },
        usageLabel() {
            return (strings.usageLabel || '{{count}} of {{limit}} resume used')
                .replace('{{count}}', String(this.ownedCount))
                .replace('{{limit}}', String(this.limits?.owned_limit));
        },
        isVisible(id) {
            return !this.deleted.includes(id);
        },
        async remove(id, name) {
            const ok = await window.confirmDialog({
                title: 'Delete Resume',
                message: 'Are you sure you want to delete this resume? This action cannot be undone.',
                itemName: name,
                confirmText: 'Yes, Delete',
                cancelText: 'Cancel',
            });
            if (!ok) return;
            this.deleting = true;
            try {
                await window.api.delete(`resumes/${id}`);
                window.toast.success('Resume deleted successfully');
                this.deleted.push(id);
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Failed to delete resume');
            } finally {
                this.deleting = false;
            }
        },
    }));
}
