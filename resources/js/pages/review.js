/** Port of pages/Review.jsx, PricingSuccess.jsx and AcceptCollaboration.jsx logic. */
export default function register(Alpine) {
    Alpine.data('reviewForm', ({ existing = null, verified = false, strings = {} } = {}) => ({
        form: {
            rating: existing?.rating || 0,
            title: existing?.title || '',
            comment: existing?.comment || '',
            is_public: existing?.is_public ?? true,
        },
        existing,
        verified,
        hovered: 0,
        errors: {},
        submitting: false,
        success: false,
        clear(field) {
            if (this.errors[field]) this.errors[field] = '';
        },
        rate(star) {
            this.form.rating = star;
            this.clear('rating');
        },
        get ratingLabel() {
            const l = strings.ratingLabels || {};
            return { 5: l.excellent || 'Excellent', 4: l.veryGood || 'Very Good', 3: l.good || 'Good', 2: l.fair || 'Fair', 1: l.poor || 'Poor' }[this.form.rating] || '';
        },
        validate() {
            const v = strings.validation || {};
            const e = {};
            if (this.form.rating === 0) e.rating = v.ratingRequired || 'Please select a rating';
            if (!this.form.title.trim()) e.title = v.titleRequired || 'Title is required';
            else if (this.form.title.trim().length < 3) e.title = v.titleMinLength || 'Title must be at least 3 characters';
            if (!this.form.comment.trim()) e.comment = v.commentRequired || 'Review comment is required';
            else if (this.form.comment.trim().length < 10) e.comment = v.commentMinLength || 'Review must be at least 10 characters';
            return e;
        },
        async submit() {
            if (this.existing) {
                window.toast.info(strings.alreadySubmittedMessage || 'You have already submitted a review.');
                return;
            }
            const errors = this.validate();
            if (Object.keys(errors).length) {
                this.errors = errors;
                return;
            }
            this.submitting = true;
            this.success = false;
            try {
                const { data } = await window.api.post('reviews', this.form, { skipValidationToast: true });
                if (data.status) {
                    this.success = true;
                    this.existing = data.data;
                    window.toast.success(strings.successMessage || 'Thank you for your review!');
                    setTimeout(() => (this.success = false), 5000);
                }
            } catch (error) {
                window.toast.error(error.response?.data?.message || strings.errorMessage || 'Failed to submit review. Please try again.');
                if (error.response?.data?.errors) {
                    this.errors = Object.fromEntries(Object.entries(error.response.data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                }
            } finally {
                this.submitting = false;
            }
        },
    }));

    Alpine.data('pricingSuccess', () => ({
        status: 'pending',
        async init() {
            const params = new URLSearchParams(window.location.search);
            const sessionId = params.get('session_id');
            const transactionId = params.get('transaction_id') || params.get('_ptxn');
            if (!sessionId && !transactionId) {
                this.status = 'missing_session';
                return;
            }
            try {
                const body = {};
                if (sessionId) body.session_id = sessionId;
                if (transactionId) body.transaction_id = transactionId;
                await window.api.post('billing/checkout/confirm', body);
                const me = await window.api.get('me');
                Alpine.store('auth').user = me.data.user;
                this.status = 'success';
            } catch {
                this.status = 'error';
            }
        },
    }));

    Alpine.data('acceptCollaboration', (token) => ({
        status: 'loading',
        message: '',
        async init() {
            try {
                const { data } = await window.api.post(`collaborate/accept/${token}`, {}, { skipAuthRedirect: true });
                if (data.status) {
                    this.status = 'success';
                    this.message = 'Invitation accepted! Redirecting to resume...';
                    window.toast.success('You can now edit this resume!');
                    setTimeout(() => (window.location.href = `/resume/edit/${data.data.resume_id}`), 2000);
                }
            } catch (error) {
                this.status = 'error';
                this.message =
                    error.response?.data?.message ||
                    'Failed to accept invitation. The link may have expired or you may need to log in.';
                if (error.response?.status === 401) {
                    setTimeout(() => (window.location.href = '/login'), 3000);
                }
            }
        },
    }));
}
