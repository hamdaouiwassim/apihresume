/** Port of the Pricing.jsx checkout handler. */
export default function register(Alpine) {
    Alpine.data('proCheckout', ({ region, isTunisia, strings }) => ({
        loading: false,
        async upgrade() {
            if (isTunisia || region === 'tunisia') {
                window.toast.info(strings.tunisiaUpgradeNote);
                return;
            }
            this.loading = true;
            try {
                const response = await window.api.post('billing/checkout', region ? { region } : {});
                const url = response?.data?.data?.url;
                if (url) {
                    window.location.href = url;
                    return;
                }
                window.toast.error(strings.checkoutError);
            } catch (err) {
                const code = err.response?.data?.code;
                const message = err.response?.data?.message;
                if (code === 'tunisia_checkout_unavailable') {
                    window.toast.info(strings.tunisiaUpgradeNote || message);
                } else if (code === 'billing_not_configured') {
                    window.toast.error(strings.billingUnavailable || message);
                } else if (code === 'already_pro') {
                    window.toast.info(strings.alreadyPro || message);
                } else if (err.response?.status !== 422) {
                    window.toast.error(message || strings.checkoutError || 'Checkout failed.');
                }
            } finally {
                this.loading = false;
            }
        },
    }));
}
