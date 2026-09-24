import { createContext, useContext } from 'react';
import { translations } from '../translations';

const LanguageContext = createContext();

/**
 * Island version: the language comes from the Laravel session (window.__APP__.locale).
 * Toggling goes through the Blade locale route so the whole page re-renders in the new language.
 */
export function LanguageProvider({ children }) {
    const language = window.__APP__?.locale === 'fr' ? 'fr' : 'en';
    const t = translations[language];

    const toggleLanguage = () => {
        window.location.href = `/locale/${language === 'en' ? 'fr' : 'en'}`;
    };

    return (
        <LanguageContext.Provider value={{ language, toggleLanguage, t }}>
            {children}
        </LanguageContext.Provider>
    );
}

export function useLanguage() {
    const context = useContext(LanguageContext);
    if (context === undefined) {
        throw new Error('useLanguage must be used within a LanguageProvider');
    }
    return context;
}
