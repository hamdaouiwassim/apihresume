/** Port of pages/Profile.jsx */
export default function register(Alpine) {
    Alpine.data('profileForm', ({ user, strings = {} }) => ({
        form: { name: user.name || '', email: user.email || '', avatar: user.avatar || '', password: '', password_confirmation: '' },
        avatarFile: null,
        errors: {},
        saving: false,
        changed: false,
        showPassword: false,
        showConfirm: false,
        githubLoading: false,
        get user() {
            return Alpine.store('auth').user;
        },
        /** Admin profile "Cancel": restore the saved values. */
        reset() {
            const u = Alpine.store('auth').user || user;
            if (this.form.avatar.startsWith('blob:')) URL.revokeObjectURL(this.form.avatar);
            this.form = { name: u.name || '', email: u.email || '', avatar: u.avatar || '', password: '', password_confirmation: '' };
            this.avatarFile = null;
            this.errors = {};
            this.changed = false;
        },
        touch(field) {
            this.changed = true;
            if (this.errors[field]) {
                const next = { ...this.errors };
                delete next[field];
                this.errors = next;
            }
        },
        validate() {
            const v = strings.validation || {};
            const e = {};
            if (!this.form.name.trim()) e.name = [v.nameRequired || 'Name is required'];
            if (!this.form.email.trim()) e.email = [v.emailRequired || 'Email is required'];
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) e.email = [v.emailInvalid || 'Please enter a valid email address'];
            if (this.form.password) {
                if (this.form.password.length < 8) e.password = [v.passwordLength || 'Password must be at least 8 characters'];
                if (this.form.password !== this.form.password_confirmation) e.password_confirmation = [v.passwordMismatch || 'Passwords do not match'];
            }
            return e;
        },
        get blocked() {
            return Object.keys(this.validate()).length > 0 || Object.keys(this.errors).length > 0 || this.saving || !this.changed;
        },
        onAvatar(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            const n = strings.notifications || {};
            if (!file.type.startsWith('image/')) {
                window.toast.error(n.invalidFileType || 'Please select an image file');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                window.toast.error(n.fileTooLarge || 'Image size should be less than 5MB');
                return;
            }
            this.avatarFile = file;
            this.form.avatar = URL.createObjectURL(file);
            this.changed = true;
        },
        async submit() {
            const errors = this.validate();
            if (Object.keys(errors).length) {
                this.errors = errors;
                return;
            }
            const n = strings.notifications || {};
            this.saving = true;
            try {
                const data = new FormData();
                data.append('name', this.form.name);
                data.append('email', this.form.email);
                if (this.avatarFile) data.append('avatar', this.avatarFile, this.avatarFile.name);
                if (this.form.password) {
                    data.append('password', this.form.password);
                    data.append('password_confirmation', this.form.password_confirmation);
                }
                data.append('_method', 'PUT');
                const response = await window.api.post('profile', data, { skipValidationToast: true });
                if (response.data.status) {
                    Alpine.store('auth').user = response.data.user;
                    if (this.form.avatar.startsWith('blob:')) URL.revokeObjectURL(this.form.avatar);
                    this.form.avatar = response.data.user.avatar || this.form.avatar;
                    this.avatarFile = null;
                    this.form.password = '';
                    this.form.password_confirmation = '';
                    this.changed = false;
                    window.toast.success(n.updateSuccess || 'Profile updated successfully!');
                }
            } catch (error) {
                if (error.response?.data?.errors) this.errors = error.response.data.errors;
                window.toast.error(error.response?.data?.message || n.updateError || 'Failed to update profile');
            } finally {
                this.saving = false;
            }
        },
        async connectGithub() {
            const g = strings.githubImport || {};
            this.githubLoading = true;
            try {
                const { data } = await window.api.get('auth/github/import/url', { params: { return_to: '/profile' } });
                if (data?.status && data?.url) {
                    window.location.href = data.url;
                    return;
                }
                window.toast.error(data?.message || g.notConfigured || 'GitHub is not configured.');
            } catch (error) {
                window.toast.error(error.response?.data?.message || g.notConfigured || 'Could not start GitHub connection.');
            } finally {
                this.githubLoading = false;
            }
        },
        async disconnectGithub() {
            const g = strings.githubImport || {};
            this.githubLoading = true;
            try {
                const res = await window.api.post('auth/github/import/disconnect');
                window.toast.success(res.data?.message || g.disconnectSuccess || 'GitHub disconnected.');
                const me = await window.api.get('me');
                if (me.data?.user) Alpine.store('auth').user = me.data.user;
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Failed to disconnect GitHub.');
            } finally {
                this.githubLoading = false;
            }
        },
    }));
}
