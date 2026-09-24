/** Port of components/CollaborationNotifications.jsx (bell + pending invitations dropdown). */
export default function collabNotifications() {
    return {
        invitations: [],
        open: false,
        loading: false,
        processing: [],
        timer: null,
        init() {
            this.fetch();
            this.timer = setInterval(() => this.fetch(), 30000);
        },
        destroy() {
            clearInterval(this.timer);
        },
        get count() {
            return this.invitations.length;
        },
        isProcessing(id) {
            return this.processing.includes(id);
        },
        async fetch() {
            try {
                this.loading = true;
                const { data } = await window.api.get('collaborations/pending');
                if (data.status) this.invitations = data.data || [];
            } catch (e) {
                // silent, like the React component
            } finally {
                this.loading = false;
            }
        },
        async accept(id) {
            if (this.isProcessing(id)) return;
            this.processing.push(id);
            try {
                const { data } = await window.api.post(`collaborations/${id}/accept`);
                if (data.status) {
                    window.toast.success('Invitation accepted! You can now edit this resume.');
                    this.invitations = this.invitations.filter((i) => i.id !== id);
                    window.location.href = '/resumes';
                }
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Failed to accept invitation');
            } finally {
                this.processing = this.processing.filter((p) => p !== id);
            }
        },
        async refuse(id) {
            if (this.isProcessing(id)) return;
            this.processing.push(id);
            try {
                const { data } = await window.api.post(`collaborations/${id}/refuse`);
                if (data.status) {
                    window.toast.success('Invitation declined');
                    this.invitations = this.invitations.filter((i) => i.id !== id);
                }
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Failed to decline invitation');
            } finally {
                this.processing = this.processing.filter((p) => p !== id);
            }
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            if (Number.isNaN(date.getTime())) return '';
            const diffInHours = Math.floor((Date.now() - date) / 36e5);
            if (diffInHours < 1) return 'Just now';
            if (diffInHours < 24) return `${diffInHours}h ago`;
            if (diffInHours < 48) return 'Yesterday';
            const diffInDays = Math.floor(diffInHours / 24);
            if (diffInDays < 7) return `${diffInDays}d ago`;
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        },
    };
}
