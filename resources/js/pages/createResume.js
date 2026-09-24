import { saveGuestResumeDraft } from './resumeDraft';

/** Port of pages/createResume.jsx (guest /resume/start and signed-in /resume/create). */
export default function register(Alpine) {
    Alpine.data('createResume', ({ templates = [], allowGuest = false, canCreate = true, initialTemplate = null, strings = {} } = {}) => ({
        templates,
        allowGuest,
        canCreate,
        name: '',
        selected: initialTemplate ?? templates[0]?.id ?? '',
        search: '',
        category: 'all',
        submitting: false,
        init() {
            if (!this.allowGuest && !this.canCreate) Alpine.store('upgrade').show('resume_limit');
            this.$nextTick(() => this.$refs.name?.focus());
        },
        get categories() {
            return ['all', ...new Set(this.templates.map((t) => t.category || 'General'))];
        },
        get filtered() {
            const q = this.search.toLowerCase();
            return this.templates.filter(
                (t) =>
                    (this.category === 'all' || t.category === this.category) &&
                    (t.name.toLowerCase().includes(q) || (t.description || '').toLowerCase().includes(q)),
            );
        },
        get canSubmit() {
            return Boolean(this.name.trim() && this.selected);
        },
        async submit() {
            if (!this.canSubmit) {
                if (this.allowGuest) window.toast.error(strings.validationError);
                else alert(strings.validationError);
                return;
            }
            if (this.allowGuest) {
                this.submitting = true;
                try {
                    saveGuestResumeDraft({ name: this.name.trim(), template_id: this.selected });
                    window.toast.next('success', strings.guestDraftSavedToast);
                    window.location.replace('/register?next=' + encodeURIComponent('/resume/claim-draft'));
                } catch {
                    window.toast.error(strings.creationError);
                    this.submitting = false;
                }
                return;
            }
            if (!this.canCreate) {
                Alpine.store('upgrade').show('resume_limit');
                return;
            }
            this.submitting = true;
            try {
                const response = await window.api.post('resumes', { name: this.name.trim(), template_id: this.selected });
                window.location.href = `/resume/edit/${response.data.data.id}`;
            } catch (err) {
                if (err.response?.data?.code === 'resume_limit_reached') {
                    this.canCreate = false;
                    Alpine.store('upgrade').show('resume_limit');
                    window.toast.error(err.response?.data?.message || strings.limitReached);
                } else if (err.response?.status !== 422) {
                    window.toast.error(strings.creationError);
                }
                this.submitting = false;
            }
        },
    }));
}
