/**
 * Login / register / social sign-in (ports of pages/login.jsx, register.jsx, SocialCallback.jsx).
 * They call the existing JSON API (session cookie auth), then do a full page load.
 */
import { mix } from '../ui/mix';

const CLAIM_DRAFT = '/resume/claim-draft';

function safeNext() {
    const next = new URLSearchParams(window.location.search).get('next');
    return next === CLAIM_DRAFT ? CLAIM_DRAFT : null;
}

function homePath(user) {
    return user?.is_admin ? '/admin' : '/resumes';
}

async function redirectToProvider(provider, fallback) {
    const { data } = await window.api.get(`auth/${provider}/url`);
    if (!data?.url) throw new Error('Missing redirect URL');
    window.location.href = data.url;
}

const socialMixin = {
    googleLoading: false,
    linkedinLoading: false,
    get socialBusy() {
        return this.googleLoading || this.linkedinLoading;
    },
    async social(provider, fallbackMessage) {
        const key = provider === 'google' ? 'googleLoading' : 'linkedinLoading';
        this[key] = true;
        try {
            await redirectToProvider(provider);
        } catch (error) {
            window.toast.error(error.response?.data?.message || fallbackMessage);
        } finally {
            this[key] = false;
        }
    },
};

export default function register(Alpine) {
    Alpine.data('loginForm', () => mix(socialMixin, {
        form: { email: '', password: '' },
        errors: {},
        submitting: false,
        async submit() {
            this.submitting = true;
            try {
                const { data } = await window.api.post('login', this.form, { skipAuthRedirect: true, skipValidationToast: true });
                if (data.requires_email_verification) {
                    window.toast.next('info', 'Please verify your email to continue.');
                }
                window.toast.next('success', 'Login successful!');
                window.location.href = safeNext() || homePath(data.user);
            } catch (error) {
                if (error.response?.data?.errors) this.errors = error.response.data.errors;
                window.toast.error(error.response?.data?.message || '❌ Verify your credentials');
            } finally {
                this.submitting = false;
            }
        },
    }));

    Alpine.data('registerForm', () => mix(socialMixin, {
        form: { name: '', email: '', password: '', password_confirmation: '' },
        errors: {},
        submitting: false,
        async submit() {
            this.submitting = true;
            try {
                await window.api.post('register', this.form, { skipAuthRedirect: true, skipValidationToast: true });
                window.toast.next('success', 'Account created! Please verify your email to continue.');
                window.location.replace(safeNext() || '/resumes');
            } catch (error) {
                if (error.response?.data?.errors) this.errors = error.response.data.errors;
                window.toast.error(error.response?.data?.message || "❌ Can't create your account");
            } finally {
                this.submitting = false;
            }
        },
    }));

    // The one-time code is exchanged server-side (AuthPageController@socialCallback); this page only
    // renders when that failed, or for the legacy no-code redirect where the session already exists.
    Alpine.data('socialCallback', (serverError = null) => ({
        status: 'processing',
        message: 'Connecting to your account...',
        async init() {
            const query = new URLSearchParams(window.location.search);
            const provider = query.get('provider') || 'google';

            if (serverError) {
                this.status = 'error';
                this.message = serverError;
                return;
            }

            try {
                const { data } = await window.api.get('me', { skipAuthRedirect: true });
                const profile = data?.user;
                if (!profile) throw new Error('Unable to fetch profile');

                const label = provider === 'linkedin' ? 'LinkedIn' : 'Google';
                window.toast.next('success', `Signed in with ${label}`);
                window.location.replace(homePath(profile));
            } catch (error) {
                this.status = 'error';
                this.message =
                    error.response?.data?.message ||
                    'Something went wrong while finalizing your login. Please try again.';
            }
        },
    }));
}
