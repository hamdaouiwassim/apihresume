import { Link } from 'react-router-dom';
import { FileText, Mail, ScrollText, ArrowRight } from 'lucide-react';
import { useLanguage } from '../context/LanguageContext';

const SUITE_ITEMS = [
  {
    key: 'resume',
    icon: FileText,
    grad: 'from-blue-500 to-indigo-600',
    href: '/resume/start',
  },
  {
    key: 'coverLetter',
    icon: Mail,
    grad: 'from-purple-500 to-fuchsia-600',
    href: '/cover-letter-builder',
  },
  {
    key: 'workCertificate',
    icon: ScrollText,
    grad: 'from-emerald-500 to-teal-600',
    href: '/work-certificate',
  },
];

export default function ApplicationSuiteSection({ variant = 'light' }) {
  const { t } = useLanguage();
  const suite = t?.welcome?.suite || {};

  const isDark = variant === 'dark';
  const sectionClass = isDark
    ? 'bg-slate-900/40 border-white/10'
    : 'bg-gradient-to-br from-slate-50 via-blue-50/40 to-purple-50/50 border-slate-200/80';
  const cardClass = isDark
    ? 'bg-white/5 border-white/10 hover:bg-white/10'
    : 'bg-white/90 border-slate-200/80 hover:border-blue-200 hover:shadow-lg';
  const titleClass = isDark ? 'text-white' : 'text-slate-900';
  const descClass = isDark ? 'text-slate-300' : 'text-slate-600';
  const badgeClass = isDark
    ? 'border-white/20 bg-white/10 text-violet-200'
    : 'border-purple-200 bg-white text-purple-700';

  return (
    <section
      className={`rounded-3xl border p-6 sm:p-10 ${sectionClass}`}
      aria-labelledby="application-suite-heading"
    >
      <div className="text-center max-w-2xl mx-auto mb-8 sm:mb-10">
        <span
          className={`inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] ${badgeClass}`}
        >
          {suite.badge || 'Complete your application'}
        </span>
        <h2
          id="application-suite-heading"
          className={`mt-4 text-2xl sm:text-3xl font-bold ${titleClass}`}
        >
          {suite.title || 'More than a resume'}
        </h2>
        <p className={`mt-2 text-sm sm:text-base leading-relaxed ${descClass}`}>
          {suite.subtitle ||
            'Build your CV, cover letter, and employment certificate in one place—free to start.'}
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
        {SUITE_ITEMS.map((item) => {
          const Icon = item.icon;
          const card = suite[item.key] || {};
          return (
            <Link
              key={item.key}
              to={item.href}
              className={`group flex flex-col rounded-2xl border p-5 sm:p-6 transition-all duration-300 ${cardClass}`}
            >
              <div
                className={`mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br ${item.grad} text-white shadow-md`}
              >
                <Icon className="h-6 w-6" aria-hidden />
              </div>
              <h3 className={`text-lg font-semibold ${titleClass}`}>
                {card.title || item.key}
              </h3>
              <p className={`mt-2 text-sm flex-1 leading-relaxed ${descClass}`}>
                {card.description || ''}
              </p>
              <span
                className={`mt-4 inline-flex items-center gap-1 text-sm font-semibold ${
                  isDark
                    ? 'text-violet-300 group-hover:text-white'
                    : 'text-blue-600 group-hover:text-purple-700'
                }`}
              >
                {card.cta || 'Learn more'}
                <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
              </span>
              {card.note ? (
                <p className={`mt-2 text-xs ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                  {card.note}
                </p>
              ) : null}
            </Link>
          );
        })}
      </div>
    </section>
  );
}
