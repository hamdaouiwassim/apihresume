import { mix } from '../../ui/mix';

/** Port of pages/admin/UserDetails.jsx + AdminUserBanPanel.jsx + AdminNewFeaturesEmailForm.jsx logic. */
export const AI_KIND_LABELS = { enhance_text: 'Enhance text', tailor_resume: 'Job targeting', ats_score: 'ATS score' };

export function defaultNewFeatureLinks() {
    const base = window.location.origin.replace(/\/$/, '');
    return [
        { label: 'My resumes', url: `${base}/resumes` },
        { label: 'Create a resume', url: `${base}/resume/create` },
        { label: 'Cover letters', url: `${base}/cover-letters` },
    ];
}

export function longDate(value) {
    if (!value) return 'N/A';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return 'N/A';
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

/** New-features announcement form (per-user or bulk). `send(payload)` returns a promise. */
export function newFeaturesForm({ send, sendBulk = null } = {}) {
    return {
        nf: {
            subject: 'New features on the app — try them out',
            headline: "What's new",
            message: "We've added improvements you'll want to try. Use the links below to test the latest features and let us know if anything feels off.",
            links: defaultNewFeatureLinks(),
            filter: 'verified',
            loading: null,
        },
        nfPayload() {
            const subject = this.nf.subject.trim();
            return {
                subject,
                headline: this.nf.headline.trim() || subject,
                message: this.nf.message.trim(),
                links: this.nf.links.map((l) => ({ label: l.label.trim(), url: l.url.trim() })).filter((l) => l.label && l.url),
            };
        },
        nfValidate() {
            const p = this.nfPayload();
            if (!p.subject || !p.message) return 'Subject and message are required.';
            if (p.links.length === 0) return 'Add at least one link with label and URL.';
            return null;
        },
        nfAddLink() {
            this.nf.links.push({ label: '', url: '' });
        },
        nfRemoveLink(i) {
            this.nf.links.splice(i, 1);
        },
        nfResetLinks() {
            this.nf.links = defaultNewFeatureLinks();
        },
        async nfSendUser() {
            const err = this.nfValidate();
            if (err) return window.toast.error(err);
            this.nf.loading = 'user';
            try {
                await send.call(this, this.nfPayload());
            } catch (e) {
                window.toast.error(e.response?.data?.message || 'Failed to queue email.');
            } finally {
                this.nf.loading = null;
            }
        },
        async nfSendBulk() {
            const err = this.nfValidate();
            if (err) return window.toast.error(err);
            if (!window.confirm(`Queue new-features email for "${this.nf.filter.replace('_', ' ')}" users?`)) return;
            this.nf.loading = 'bulk';
            try {
                await sendBulk.call(this, { ...this.nfPayload(), filter: this.nf.filter });
            } catch (e) {
                window.toast.error(e.response?.data?.message || 'Failed to queue bulk emails.');
            } finally {
                this.nf.loading = null;
            }
        },
    };
}

export default function register(Alpine) {
    window.newFeaturesForm = newFeaturesForm;

    Alpine.data('adminUserDetails', (id) =>
        mix(
            newFeaturesForm({
                async send(payload) {
                    const res = await window.api.post(`admin/users/${id}/emails/new-features`, payload);
                    if (res.data?.status) {
                        window.toast.success(res.data.message || 'New features email queued');
                        this.fetch();
                    }
                },
            }),
            {
                id,
                user: null,
                loading: true,
                reminderLoading: null,
                emailForm: { subject: '', message: '' },
                sendingEmail: false,
                banDuration: '7_days',
                banReason: '',
                banLoading: null,
                AI_KIND_LABELS,
                longDate,
                init() {
                    this.fetch();
                },
                async fetch() {
                    try {
                        const { data } = await window.api.get(`admin/users/${this.id}`);
                        if (data.status) {
                            this.user = data.data;
                        } else {
                            window.toast.next('error', 'Failed to load user details');
                            window.location.href = '/admin/users';
                        }
                    } catch {
                        window.toast.next('error', 'Failed to load user details');
                        window.location.href = '/admin/users';
                    } finally {
                        this.loading = false;
                    }
                },
                get avatar() {
                    return this.user?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(this.user?.email || this.user?.name || 'user')}`;
                },
                timeAgo(value) {
                    if (!value) return 'Never';
                    const s = Math.floor((Date.now() - new Date(value)) / 1000);
                    if (s < 60) return 'Just now';
                    if (s < 3600) return `${Math.floor(s / 60)} minutes ago`;
                    if (s < 86400) return `${Math.floor(s / 3600)} hours ago`;
                    if (s < 2592000) return `${Math.floor(s / 86400)} days ago`;
                    return longDate(value);
                },
                num(n) {
                    return Number(n || 0).toLocaleString();
                },
                statusBadge(status) {
                    return window.outboundStatus(status);
                },
                typeLabel(type) {
                    return window.outboundType(type);
                },
                async togglePro() {
                    const next = !this.user.is_pro;
                    if (next && !this.user.email_verified_at) {
                        window.toast.error('Only verified users can be granted Pro access');
                        return;
                    }
                    try {
                        const { data } = await window.api.put(`admin/users/${this.user.id}`, { is_pro: next });
                        if (data.status) {
                            this.user = data.data;
                            window.toast.success(next ? 'User upgraded to Pro' : 'Pro access removed');
                        }
                    } catch (error) {
                        window.toast.error(error.response?.data?.message || 'Failed to update Pro status');
                    }
                },
                showResume(resumeId) {
                    window.dispatchEvent(new CustomEvent('admin-resume-preview', { detail: { id: resumeId } }));
                },
                async deleteResume(resume) {
                    const ok = await window.confirmDialog({
                        title: 'Delete resume',
                        message: 'This permanently deletes the CV and all its sections. This cannot be undone.',
                        itemName: resume.name,
                        confirmText: 'Delete',
                        cancelText: 'Cancel',
                    });
                    if (!ok) return;
                    try {
                        const { data } = await window.api.delete(`admin/resumes/${resume.id}`);
                        if (data.status) {
                            window.toast.success('Resume deleted');
                            window.dispatchEvent(new CustomEvent('admin-resume-preview-close'));
                            this.fetch();
                        } else {
                            window.toast.error(data.message || 'Failed to delete resume');
                        }
                    } catch (error) {
                        window.toast.error(error.response?.data?.message || 'Failed to delete resume');
                    }
                },
                async reminder(kind) {
                    const map = {
                        resume: ['resume-reminder', 'Resume reminder queued', 'Failed to queue resume reminder'],
                        verify: ['verification-reminder', 'Verification reminder queued', 'Failed to queue verification reminder'],
                    };
                    const [path, ok, fail] = map[kind];
                    this.reminderLoading = kind;
                    try {
                        const res = await window.api.post(`admin/users/${this.user.id}/emails/${path}`);
                        window.toast.success(res.data?.message || ok);
                        this.fetch();
                    } catch (e) {
                        window.toast.error(e.response?.data?.message || fail);
                    } finally {
                        this.reminderLoading = null;
                    }
                },
                async sendMessage() {
                    if (!this.emailForm.subject.trim() || !this.emailForm.message.trim()) {
                        window.toast.error('Subject and message are required.');
                        return;
                    }
                    this.sendingEmail = true;
                    try {
                        await window.api.post(`admin/users/${this.user.id}/message`, this.emailForm);
                        window.toast.success('Email queued successfully.');
                        this.emailForm = { subject: '', message: '' };
                        this.fetch();
                    } catch (error) {
                        window.toast.error(error?.response?.data?.message || 'Failed to send email.');
                    } finally {
                        this.sendingEmail = false;
                    }
                },
                async ban() {
                    if (this.banDuration === 'permanent' && !window.confirm('Permanently ban this user until an admin lifts the ban?')) return;
                    this.banLoading = 'ban';
                    try {
                        const res = await window.api.post(`admin/users/${this.user.id}/ban`, {
                            duration: this.banDuration,
                            reason: this.banReason.trim() || undefined,
                        });
                        if (res.data?.status) {
                            window.toast.success(res.data.message || 'User banned');
                            this.fetch();
                        }
                    } catch (e) {
                        window.toast.error(e.response?.data?.message || 'Failed to ban user');
                    } finally {
                        this.banLoading = null;
                    }
                },
                async unban() {
                    this.banLoading = 'unban';
                    try {
                        const res = await window.api.post(`admin/users/${this.user.id}/unban`);
                        if (res.data?.status) {
                            window.toast.success(res.data.message || 'Ban lifted');
                            this.fetch();
                        }
                    } catch (e) {
                        window.toast.error(e.response?.data?.message || 'Failed to lift ban');
                    } finally {
                        this.banLoading = null;
                    }
                },
            },
        ),
    );
}
