import { useLanguage } from '../context/LanguageContext';

export default function LanguageToggle({ tone = 'light' }) {
    const { language, toggleLanguage } = useLanguage();

    const isDark = tone === 'dark';

    return (
        <button
            onClick={toggleLanguage}
            type="button"
            className={`inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-400/50 transition-colors ${
                isDark
                    ? 'border border-white/25 bg-white/10 text-slate-100 hover:bg-white/15'
                    : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-offset-2 focus:ring-blue-500'
            }`}
        >
            {language === 'en' ? '🇫🇷 FR' : '🇬🇧 EN'}
        </button>
    );
}