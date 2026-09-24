import { Link } from "react-router-dom";
import { Sparkles, Infinity, Crown } from "lucide-react";
import { useLanguage } from "../context/LanguageContext";
import { isAiUnlimited, isProPlan } from "../utils/aiCredits";

/**
 * Shows monthly AI token credits (used / total / remaining).
 */
export default function AiTokenCredits({ user, compact = false, className = "" }) {
  const { language } = useLanguage();
  const fr = language === "fr";
  const tokens = user?.ai_tokens;
  const isPro = isProPlan(user);

  if (!tokens || isAiUnlimited(user)) {
    if (compact) return null;
    return (
      <div
        className={`rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 flex items-center gap-2 text-sm text-emerald-800 ${className}`}
      >
        <Infinity className="h-4 w-4 shrink-0" />
        <span className="font-medium">
          {fr ? "IA illimitée (admin)" : "Unlimited AI (admin)"}
        </span>
      </div>
    );
  }

  const used = tokens.credits_used ?? tokens.tokens_used ?? 0;
  const total = tokens.credits_total ?? tokens.token_limit ?? (isPro ? 50000 : 1000);
  const remaining = tokens.credits_remaining ?? tokens.tokens_remaining ?? Math.max(0, total - used);
  const pct = tokens.percent_used ?? (total > 0 ? Math.min(100, Math.round((used / total) * 100)) : 0);
  const exhausted = remaining <= 0;

  const title = isPro
    ? fr
      ? "Crédits IA (plan Pro)"
      : "AI credits (Pro plan)"
    : fr
      ? "Crédits IA (plan gratuit)"
      : "AI credits (free plan)";

  const barColor =
    pct >= 90 ? "bg-red-500" : pct >= 70 ? "bg-amber-500" : isPro
      ? "bg-gradient-to-r from-amber-500 to-orange-500"
      : "bg-gradient-to-r from-violet-500 to-fuchsia-500";

  const compactClasses = exhausted
    ? "bg-red-100 text-red-800"
    : isPro
      ? "bg-amber-100 text-amber-900"
      : "bg-violet-100 text-violet-800";

  if (compact) {
    return (
      <span
        className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums ${compactClasses} ${className}`}
        title={
          fr
            ? `${remaining} crédits IA restants ce mois`
            : `${remaining} AI credits left this month`
        }
      >
        <Sparkles className="h-3 w-3" />
        {remaining.toLocaleString()} / {total.toLocaleString()}
      </span>
    );
  }

  const cardBorder = exhausted
    ? "border-red-200 bg-red-50/60"
    : isPro
      ? "border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50/80"
      : "border-violet-200 bg-gradient-to-br from-violet-50 to-fuchsia-50/80";

  return (
    <div className={`rounded-2xl border ${cardBorder} p-4 sm:p-5 shadow-sm ${className}`}>
      <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
        <div className="flex items-start gap-3">
          <span
            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ${
              isPro ? "text-amber-600" : "text-violet-600"
            }`}
          >
            <Sparkles className="h-5 w-5" />
          </span>
          <div>
            <h3 className="text-sm font-bold text-gray-900">{title}</h3>
            <p className="text-xs text-gray-600 mt-0.5">
              {fr
                ? "Renouvelés chaque mois calendaire. Toutes les fonctions IA partagent ce pool."
                : "Reset each calendar month. All AI features share this pool."}
            </p>
          </div>
        </div>
        {exhausted && !isPro && (
          <Link
            to="/pricing"
            className="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-3 py-2 text-xs font-bold text-white shadow-sm hover:opacity-95 shrink-0"
          >
            <Crown className="h-3.5 w-3.5" />
            {fr ? "Passer Pro" : "Upgrade Pro"}
          </Link>
        )}
        {exhausted && isPro && (
          <p className="text-xs font-medium text-red-800 shrink-0 max-w-[200px]">
            {fr
              ? "Quota Pro épuisé pour ce mois. Réessayez le mois prochain ou contactez le support."
              : "Pro quota used for this month. Try again next month or contact support."}
          </p>
        )}
      </div>

      <div className="grid grid-cols-3 gap-3 mb-3 text-center">
        <div className={`rounded-xl bg-white/80 px-2 py-2 ring-1 ${isPro ? "ring-amber-100" : "ring-violet-100"}`}>
          <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
            {fr ? "Utilisés" : "Used"}
          </p>
          <p className="text-lg font-bold text-gray-900 tabular-nums">{used.toLocaleString()}</p>
        </div>
        <div className={`rounded-xl bg-white/80 px-2 py-2 ring-1 ${isPro ? "ring-amber-100" : "ring-violet-100"}`}>
          <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
            {fr ? "Restants" : "Left"}
          </p>
          <p className={`text-lg font-bold tabular-nums ${exhausted ? "text-red-600" : isPro ? "text-amber-700" : "text-violet-700"}`}>
            {remaining.toLocaleString()}
          </p>
        </div>
        <div className={`rounded-xl bg-white/80 px-2 py-2 ring-1 ${isPro ? "ring-amber-100" : "ring-violet-100"}`}>
          <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
            {fr ? "Total" : "Total"}
          </p>
          <p className="text-lg font-bold text-gray-900 tabular-nums">{total.toLocaleString()}</p>
        </div>
      </div>

      <div className={`h-2.5 w-full overflow-hidden rounded-full bg-white/90 ring-1 ${isPro ? "ring-amber-100" : "ring-violet-100"}`}>
        <div
          className={`h-full rounded-full transition-all duration-500 ${barColor}`}
          style={{ width: `${Math.min(100, pct)}%` }}
        />
      </div>
      <p className="mt-2 text-xs text-gray-600 tabular-nums">
        {pct}% {fr ? "du quota mensuel utilisé" : "of monthly quota used"}
        {tokens.month ? ` · ${tokens.month}` : ""}
      </p>
    </div>
  );
}
