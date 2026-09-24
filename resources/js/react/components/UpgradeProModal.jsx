import { Crown, Sparkles, X } from "lucide-react";
import { Link } from "react-router-dom";
import { useLanguage } from "../context/LanguageContext";
import { usePricingRegion } from "../hooks/usePricingRegion";
import { createPortal } from "react-dom";

/**
 * @param {{ isOpen: boolean; onClose: () => void; upgradePath?: string; variant?: "default" | "quota" | "resume_limit" }} props
 */
export default function UpgradeProModal({
  isOpen,
  onClose,
  upgradePath = "/pricing",
  variant = "default",
}) {
  const { language } = useLanguage();
  const { proPrice } = usePricingRegion();

  if (!isOpen) return null;

  const title = language === "fr" ? "Passez a Pro" : "Upgrade to Pro";
  const subtitleDefault =
    language === "fr"
      ? "L'amelioration IA et les outils avances sont inclus dans l'offre Pro."
      : "AI enhancement and advanced tools are included on the Pro plan.";
  const subtitleQuota =
    language === "fr"
      ? "Vous avez utilise toutes vos utilisations IA gratuites ce mois-ci. Passez a Pro pour un acces illimite."
      : "You have used all free AI credits for this month. Upgrade to Pro for unlimited AI and full ATS insights.";
  const subtitleResumeLimit =
    language === "fr"
      ? "L'offre gratuite inclut un seul CV. Passez a Pro pour en creer autant que vous voulez."
      : "The free plan includes one resume. Upgrade to Pro to create unlimited resumes.";
  const subtitle =
    variant === "quota"
      ? subtitleQuota
      : variant === "resume_limit"
        ? subtitleResumeLimit
        : subtitleDefault;
  const cta =
    language === "fr"
      ? `Voir l'offre Pro (${proPrice})`
      : `View Pro Plan (${proPrice})`;
  const later = language === "fr" ? "Plus tard" : "Maybe later";

  const modalContent = (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
    >
      <div className="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
        <div className="p-5 border-b border-slate-100 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Crown className="h-5 w-5 text-amber-500" />
            <h3 className="text-lg font-bold text-slate-900">{title}</h3>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100"
            aria-label="Close"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <div className="p-5 space-y-4">
          <p className="text-sm text-slate-700">{subtitle}</p>
          <div className="rounded-xl border border-indigo-100 bg-indigo-50 p-3">
            <p className="text-xs font-semibold uppercase tracking-wide text-indigo-700 mb-2">
              Pro feature
            </p>
            <div className="flex items-start gap-2 text-sm text-indigo-900">
              <Sparkles className="h-4 w-4 mt-0.5" />
              <span>
                {language === "fr"
                  ? "IA illimitee, ATS complet, priorite de traitement"
                  : "Unlimited AI, full ATS keyword insights, priority processing"}
              </span>
            </div>
          </div>
          <div className="flex justify-end gap-2">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50"
            >
              {later}
            </button>
            <Link
              to={upgradePath}
              className="px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold hover:from-blue-700 hover:to-indigo-700"
              onClick={onClose}
            >
              {cta}
            </Link>
          </div>
        </div>
      </div>
    </div>
  );

  if (typeof document === "undefined") {
    return null;
  }

  return createPortal(modalContent, document.body);
}
