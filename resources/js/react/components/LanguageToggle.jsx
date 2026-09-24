import { useId } from 'react';
import { useLanguage } from '../context/LanguageContext';

/** Inline SVG flags (emoji flags are not rendered on Windows). Mirrors resources/views/components/flag.blade.php. */
function Flag({ country }) {
    const clipId = `flag-gb-${useId().replace(/:/g, '')}`;
    const className = 'h-3.5 w-5 rounded-[2px] shadow-sm ring-1 ring-black/10';

    if (country === 'fr') {
        return (
            <svg className={className} viewBox="0 0 3 2" aria-hidden="true" preserveAspectRatio="none">
                <rect width="1" height="2" x="0" fill="#0055A4" />
                <rect width="1" height="2" x="1" fill="#FFFFFF" />
                <rect width="1" height="2" x="2" fill="#EF4135" />
            </svg>
        );
    }

    return (
        <svg className={className} viewBox="0 0 60 30" aria-hidden="true" preserveAspectRatio="none">
            <clipPath id={clipId}><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z" /></clipPath>
            <path d="M0,0 v30 h60 v-30 z" fill="#012169" />
            <path d="M0,0 L60,30 M60,0 L0,30" stroke="#FFFFFF" strokeWidth="6" />
            <path d="M0,0 L60,30 M60,0 L0,30" clipPath={`url(#${clipId})`} stroke="#C8102E" strokeWidth="4" />
            <path d="M30,0 v30 M0,15 h60" stroke="#FFFFFF" strokeWidth="10" />
            <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" strokeWidth="6" />
        </svg>
    );
}

export default function LanguageToggle({ tone = 'light' }) {
    const { language, toggleLanguage } = useLanguage();
    const next = language === 'en' ? 'fr' : 'en';
    const isDark = tone === 'dark';

    return (
        <button
            onClick={toggleLanguage}
            type="button"
            title={next === 'fr' ? 'Français' : 'English'}
            className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-400/50 transition-colors ${
                isDark
                    ? 'border border-white/25 bg-white/10 text-slate-100 hover:bg-white/15'
                    : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-offset-2 focus:ring-blue-500'
            }`}
        >
            <Flag country={next} />
            <span>{next.toUpperCase()}</span>
        </button>
    );
}
