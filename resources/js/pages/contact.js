/** Port of pages/ContactUs.jsx form state (submission is still simulated, as in the React version). */
export default function register(Alpine) {
    Alpine.data('contactForm', (messages = {}) => ({
        form: { name: '', email: '', subject: '', message: '' },
        errors: {},
        submitting: false,
        status: null,
        clearError(field) {
            if (this.errors[field]) this.errors[field] = '';
        },
        validate() {
            const e = {};
            const f = this.form;
            if (!f.name.trim()) e.name = messages.nameRequired;
            if (!f.email.trim()) e.email = messages.emailRequired;
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email)) e.email = messages.emailInvalid;
            if (!f.subject.trim()) e.subject = messages.subjectRequired;
            if (!f.message.trim()) e.message = messages.messageRequired;
            else if (f.message.trim().length < 10) e.message = messages.messageMinLength;
            return e;
        },
        async submit() {
            const errors = this.validate();
            if (Object.keys(errors).length > 0) {
                this.errors = errors;
                return;
            }
            this.submitting = true;
            this.status = null;
            try {
                // TODO (unchanged from the React page): replace with a real contact endpoint.
                await new Promise((resolve) => setTimeout(resolve, 1000));
                this.status = 'success';
                this.form = { name: '', email: '', subject: '', message: '' };
                this.errors = {};
                setTimeout(() => (this.status = null), 5000);
            } catch {
                this.status = 'error';
                setTimeout(() => (this.status = null), 5000);
            } finally {
                this.submitting = false;
            }
        },
    }));
}
