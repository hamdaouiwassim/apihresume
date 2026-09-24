/**
 * AI credit helpers + Alpine components ported from:
 *   utils/aiCredits.js, components/EnhanceTextareaButton.jsx, components/UpgradeProModal.jsx,
 *   components/AiTokenCredits.jsx
 */

export function hasAiTokenBudget(user) {
    const t = user?.ai_tokens;
    if (!t) return true;
    if (t.is_unlimited) return true;
    if (t.credits_remaining == null) return true;
    return t.credits_remaining > 0;
}

export function isAiUnlimited(user) {
    return Boolean(user?.is_admin || user?.ai_tokens?.is_unlimited);
}

export function isProPlan(user) {
    return Boolean(user?.is_pro && !user?.is_admin);
}

/** Merge ai_quota / ai_tokens from an API response into the shared auth store. */
export function syncAiUsage(Alpine, data) {
    if (!data) return;
    const { ai_quota, ai_tokens } = data;
    if (ai_quota === undefined && ai_tokens === undefined) return;
    const store = Alpine.store('auth');
    if (!store.user) return;
    store.user = {
        ...store.user,
        ...(ai_quota !== undefined ? { ai_quota } : {}),
        ...(ai_tokens !== undefined ? { ai_tokens } : {}),
    };
}

const PRICE_CACHE_KEY = 'hresume_pricing_region';

export function registerAi(Alpine) {
    Alpine.store('auth', {
        user: window.__APP__?.user ?? null,
    });

    Alpine.store('upgrade', {
        open: false,
        variant: 'default',
        path: '/pricing',
        proPrice: null,
        show(variant = 'default', path = '/pricing') {
            this.variant = variant;
            this.path = path;
            this.open = true;
            this.loadPrice();
        },
        close() {
            this.open = false;
            this.variant = 'default';
        },
        async loadPrice() {
            if (this.proPrice) return;
            try {
                const cached = JSON.parse(sessionStorage.getItem(PRICE_CACHE_KEY) || 'null');
                if (cached?.data?.pro?.formatted && Date.now() - cached.cachedAt < 864e5) {
                    this.proPrice = cached.data.pro.formatted;
                    return;
                }
            } catch {
                // ignore
            }
            try {
                const { data } = await window.api.get('pricing-region');
                if (data?.status && data?.data) {
                    this.proPrice = data.data.pro?.formatted ?? '$5';
                    try {
                        sessionStorage.setItem(PRICE_CACHE_KEY, JSON.stringify({ data: data.data, cachedAt: Date.now() }));
                    } catch {
                        // ignore
                    }
                }
            } catch {
                this.proPrice = '$5';
            }
        },
    });

    window.aiCredits = { hasAiTokenBudget, isAiUnlimited, isProPlan, sync: (data) => syncAiUsage(Alpine, data) };

    /**
     * <div x-data="enhanceButton({ value: () => form.message, apply: (v) => form.message = v, context: 'contact message' })">
     */
    Alpine.data('enhanceButton', ({ value, apply, context = 'resume', upgradePath = '/pricing', disabled = () => false } = {}) => ({
        enhancing: false,
        originalText: '',
        enhancedText: '',
        get fr() {
            return window.__APP__?.locale === 'fr';
        },
        get user() {
            return Alpine.store('auth').user;
        },
        get unlimited() {
            return isAiUnlimited(this.user);
        },
        get canEnhance() {
            if (this.unlimited) return true;
            const q = this.user?.ai_quota;
            const tokenOk = hasAiTokenBudget(this.user);
            if (!q || q.legacy) return tokenOk;
            return (q.enhance?.remaining ?? 0) > 0 && tokenOk;
        },
        get isDisabled() {
            return this.enhancing || disabled();
        },
        get proTooltip() {
            if (isProPlan(this.user)) {
                return this.fr ? 'Quota IA Pro epuise pour ce mois' : 'Pro AI token quota used for this month';
            }
            return this.fr
                ? 'Credits IA gratuits epuises — passez a Pro (50 000 tokens/mois)'
                : 'Free AI credits used — upgrade to Pro (50,000 tokens/month)';
        },
        get showQuotaLine() {
            return !this.unlimited && this.user?.ai_quota && !this.user.ai_quota.legacy;
        },
        async enhance() {
            if (!this.unlimited && !this.canEnhance) {
                Alpine.store('upgrade').show('quota', upgradePath);
                return;
            }
            const text = (value() || '').trim();
            if (!text) {
                window.toast.error('Please write some text before using AI enhancement.');
                return;
            }
            this.enhancing = true;
            try {
                const response = await window.api.post('ai/enhance-text', { text, context }, { timeout: 55000 });
                syncAiUsage(Alpine, response.data);
                const enhanced = response.data?.data?.enhanced_text;
                if (!enhanced) {
                    window.toast.error('Unable to enhance text right now.');
                    return;
                }
                this.originalText = text;
                this.enhancedText = enhanced;
                window.toast.success(this.fr ? 'Version amelioree prete. Verifiez puis appliquez.' : 'Enhanced version ready. Review and apply.');
            } catch (error) {
                const code = error.response?.data?.code;
                if (code === 'AI_QUOTA_EXCEEDED' || code === 'AI_TOKEN_LIMIT_EXCEEDED') {
                    syncAiUsage(Alpine, error.response?.data);
                    Alpine.store('upgrade').show('quota', upgradePath);
                    window.toast.error(
                        error.response?.data?.message || (this.fr ? 'Crédits IA épuisés pour ce mois.' : 'AI credits exhausted for this month.'),
                    );
                } else if (error.response?.status !== 401) {
                    window.toast.error(error.response?.data?.message || 'Unable to enhance text right now.');
                }
            } finally {
                this.enhancing = false;
            }
        },
        applyEnhancement() {
            if (!this.enhancedText) return;
            apply(this.enhancedText);
            this.enhancedText = '';
            this.originalText = '';
            window.toast.success(this.fr ? 'Texte ameliore applique.' : 'Enhanced text applied.');
        },
        dismiss() {
            this.enhancedText = '';
            this.originalText = '';
        },
    }));

    /** Reactive view model for <x-ai-token-credits> */
    Alpine.data('aiTokenCredits', () => ({
        get user() {
            return Alpine.store('auth').user;
        },
        get tokens() {
            return this.user?.ai_tokens;
        },
        get unlimited() {
            return !this.tokens || isAiUnlimited(this.user);
        },
        get isPro() {
            return isProPlan(this.user);
        },
        get used() {
            return this.tokens?.credits_used ?? this.tokens?.tokens_used ?? 0;
        },
        get total() {
            return this.tokens?.credits_total ?? this.tokens?.token_limit ?? (this.isPro ? 50000 : 1000);
        },
        get remaining() {
            return this.tokens?.credits_remaining ?? this.tokens?.tokens_remaining ?? Math.max(0, this.total - this.used);
        },
        get pct() {
            return this.tokens?.percent_used ?? (this.total > 0 ? Math.min(100, Math.round((this.used / this.total) * 100)) : 0);
        },
        get exhausted() {
            return this.remaining <= 0;
        },
        fmt(n) {
            return Number(n || 0).toLocaleString();
        },
    }));
}
