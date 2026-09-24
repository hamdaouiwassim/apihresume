/** Port of pages/PersonalWebsiteView.jsx interactive bits (theme, section toggles, public PDF). */
const THEMES = {
    indigo: { hero: 'from-indigo-700 via-purple-700 to-indigo-800', badge: 'bg-indigo-50 text-indigo-700', chip: 'bg-indigo-50 text-indigo-700' },
    emerald: { hero: 'from-emerald-700 via-teal-700 to-emerald-800', badge: 'bg-emerald-50 text-emerald-700', chip: 'bg-emerald-50 text-emerald-700' },
    slate: { hero: 'from-slate-800 via-slate-700 to-slate-900', badge: 'bg-slate-100 text-slate-700', chip: 'bg-slate-100 text-slate-700' },
};
const ALL = Object.values(THEMES).flatMap((t) => [t.hero, t.badge, t.chip].join(' ').split(' '));

export default function register(Alpine) {
    Alpine.data('personalWebsite', ({ slug = null, canPdf = false, filename = 'cv.pdf' } = {}) => ({
        theme: 'indigo',
        show: { summary: true, experience: true, projects: true, skills: true, education: true, certifications: true },
        downloading: false,
        get fr() {
            return window.__APP__?.locale === 'fr';
        },
        /** Apply one theme slot (hero | badge | chip) to an element, removing other themes' classes. */
        themed(el, slot, active = true, inactive = 'bg-slate-100 text-slate-600') {
            el.classList.remove(...ALL, ...inactive.split(' '));
            el.classList.add(...(active ? THEMES[this.theme][slot] : inactive).split(' '));
        },
        async downloadPdf() {
            if (!canPdf || !slug) {
                window.toast.error(this.fr ? 'Le PDF public est disponible pour les profils /u/votre-slug.' : 'Public PDF download is available for stable /u/your-slug profiles.');
                return;
            }
            this.downloading = true;
            try {
                window.toast.loading(this.fr ? 'Generation du PDF...' : 'Generating PDF...', { id: 'public-pdf' });
                const response = await window.api.get(`public/profile/${slug}/pdf`, { responseType: 'blob', params: { locale: this.fr ? 'fr' : 'en' } });
                const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
                window.toast.success(this.fr ? 'PDF telecharge.' : 'PDF downloaded.', { id: 'public-pdf' });
            } catch (err) {
                window.toast.error(err.response?.data?.message || (this.fr ? 'Echec du PDF.' : 'Could not download PDF.'), { id: 'public-pdf' });
            } finally {
                this.downloading = false;
            }
        },
    }));
}
