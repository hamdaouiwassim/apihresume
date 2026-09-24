/** Footer newsletter form (GuestLayout handleSubscribe). */
export default function newsletter() {
    return {
        email: '',
        subscribing: false,
        subscribed: false,
        async submit() {
            if (!this.email.trim()) {
                window.toast.error('Please enter a valid email address');
                return;
            }
            this.subscribing = true;
            try {
                const { data } = await window.api.post('subscribers/subscribe', { email: this.email });
                if (data.status) {
                    window.toast.success(data.message || 'Successfully subscribed to our newsletter!');
                    this.subscribed = true;
                    this.email = '';
                    setTimeout(() => (this.subscribed = false), 3000);
                }
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Failed to subscribe. Please try again later.');
            } finally {
                this.subscribing = false;
            }
        },
    };
}
