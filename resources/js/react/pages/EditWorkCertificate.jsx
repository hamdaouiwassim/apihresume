import { useState, useEffect, useContext, useMemo } from "react";
import { useParams, useNavigate } from "react-router-dom";
import AuthLayout from "../Layouts/AuthLayout";
import { useLanguage } from "../context/LanguageContext";
import { AuthContext } from "../context/AuthContext";
import {
  getWorkCertificate,
  createWorkCertificate,
  updateWorkCertificate,
  downloadWorkCertificatePdf,
} from "../services/workCertificateService";
import { Save, ArrowLeft, Download, Loader2, ScrollText, Layout as LayoutIcon } from "lucide-react";
import { toast } from "sonner";

function toInputDate(value) {
  if (!value) return "";
  const s = String(value);
  return s.length >= 10 ? s.slice(0, 10) : "";
}

/** Matches API `WorkCertificateController::pdfStrings` when PDF is requested with the same `locale` as the app UI. */
const PREVIEW_I18N = {
  en: {
    heading: "Certificate of employment",
    to_whom: "To whom it may concern,",
    body_intro: "This is to certify that",
    was_employed: "was employed at",
    as: "in the position of",
    from: "from",
    to: "to",
    present: "present",
    duties: "Main responsibilities and duties included:",
    closing:
      "This certificate is issued upon the employee's request for whatever legal purpose it may serve.",
    signature_line: "Authorized signature",
    disclaimer: "This document must be signed by the employer.",
  },
  fr: {
    heading: "Attestation de travail",
    to_whom: "À qui de droit,",
    body_intro: "Nous certifions que",
    was_employed: "a été employé(e) au sein de",
    as: "en qualité de",
    from: "du",
    to: "au",
    present: "à ce jour",
    duties: "Principales responsabilités et missions :",
    closing:
      "La présente attestation est délivrée à la demande de l'intéressé(e) pour servir et valoir ce que de droit.",
    signature_line: "Signature et cachet de l'employeur",
    disclaimer: "Le document doit être signé par l'employeur.",
  },
};

function formatLongDate(yyyyMmDd, locale) {
  if (!yyyyMmDd) return "";
  const d = new Date(`${yyyyMmDd}T12:00:00`);
  if (Number.isNaN(d.getTime())) return yyyyMmDd;
  return d.toLocaleDateString(locale === "fr" ? "fr-FR" : "en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function WorkCertificatePreviewBody({ formData, localeKey }) {
  const loc = localeKey === "fr" ? "fr" : "en";
  const s = PREVIEW_I18N[loc];

  const previewDates = useMemo(() => {
    const letterDateFmt = formData.letter_date
      ? formatLongDate(formData.letter_date, loc)
      : formatLongDate(
          `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, "0")}-${String(new Date().getDate()).padStart(2, "0")}`,
          loc
        );
    const startFmt = formatLongDate(formData.employment_start, loc);
    const endFmt = formData.is_current_employment
      ? null
      : formData.employment_end
        ? formatLongDate(formData.employment_end, loc)
        : "";
    return { letterDateFmt, startFmt, endFmt };
  }, [formData.letter_date, formData.employment_start, formData.employment_end, formData.is_current_employment, loc]);

  return (
    <>
      <h2 className="text-center text-base font-bold tracking-wide mb-7">{s.heading}</h2>

      <div className="text-right text-[10pt] text-slate-600 mb-6">
        {formData.letter_place ? `${formData.letter_place}, ` : ""}
        {previewDates.letterDateFmt}
      </div>

      <p className="mb-3">
        <strong>{s.to_whom}</strong>
      </p>

      <p className="text-justify mb-3">
        {s.body_intro} <strong>{formData.employee_name || "—"}</strong> {s.was_employed}{" "}
        <strong>{formData.company_name || "—"}</strong>
        {formData.employee_job_title ? (
          <>
            , {s.as} <strong>{formData.employee_job_title}</strong>
          </>
        ) : null}
        . {s.from} <strong>{previewDates.startFmt || "—"}</strong>{" "}
        {formData.is_current_employment ? (
          <>
            {s.to} <strong>{s.present}</strong>.
          </>
        ) : (
          <>
            {s.to} <strong>{previewDates.endFmt || "—"}</strong>.
          </>
        )}
      </p>

      {formData.company_address?.trim() ? (
        <div className="my-4 p-3 border border-slate-200 bg-slate-50 rounded text-sm whitespace-pre-wrap">
          {formData.company_address}
        </div>
      ) : null}

      {formData.duties_summary?.trim() ? (
        <div className="mt-4">
          <p className="font-bold mb-1">{s.duties}</p>
          <p className="text-justify whitespace-pre-wrap text-slate-700">{formData.duties_summary}</p>
        </div>
      ) : null}

      <p className="text-justify mt-5">{s.closing}</p>

      <div className="mt-10">
        <p className="font-bold">{s.signature_line}</p>
        {formData.signer_name_title?.trim() ? <p className="mt-2">{formData.signer_name_title}</p> : null}
      </div>

      <p className="mt-8 pt-3 border-t border-slate-200 text-[8.5pt] text-slate-600 print:hidden">{s.disclaimer}</p>
    </>
  );
}

const inputClass =
  "mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500";

export default function EditWorkCertificate() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { t, language } = useLanguage();
  const { user } = useContext(AuthContext);
  const isEdit = Boolean(id);

  const strings = t.workCertificate || {};

  const [formData, setFormData] = useState({
    title: language === "fr" ? "Attestation de travail" : "Certificate of Employment",
    employee_name: "",
    employee_job_title: "",
    company_name: "",
    company_address: "",
    employment_start: "",
    employment_end: "",
    is_current_employment: false,
    duties_summary: "",
    letter_place: "",
    letter_date: "",
    signer_name_title: "",
  });

  const [isLoading, setIsLoading] = useState(isEdit);
  const [isSaving, setIsSaving] = useState(false);
  const [isDownloading, setIsDownloading] = useState(false);

  const appLocaleKey = language === "fr" ? "fr" : "en";

  useEffect(() => {
    if (id) {
      fetchCertificate();
    } else if (user?.name) {
      setFormData((prev) => ({
        ...prev,
        employee_name: prev.employee_name || user.name,
      }));
    }
  }, [id, user?.name]);

  const fetchCertificate = async () => {
    setIsLoading(true);
    try {
      const response = await getWorkCertificate(id);
      if (response.data.status) {
        const d = response.data.data;
        setFormData({
          title: d.title || "",
          employee_name: d.employee_name || "",
          employee_job_title: d.employee_job_title || "",
          company_name: d.company_name || "",
          company_address: d.company_address || "",
          employment_start: toInputDate(d.employment_start),
          employment_end: toInputDate(d.employment_end),
          is_current_employment: Boolean(d.is_current_employment),
          duties_summary: d.duties_summary || "",
          letter_place: d.letter_place || "",
          letter_date: toInputDate(d.letter_date),
          signer_name_title: d.signer_name_title || "",
        });
      }
    } catch {
      toast.error(strings.messages?.loadError || "Failed to load");
      navigate("/work-certificates");
    } finally {
      setIsLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === "checkbox" ? checked : value,
    }));
  };

  const buildPayload = () => ({
    title: formData.title.trim(),
    employee_name: formData.employee_name.trim(),
    employee_job_title: formData.employee_job_title.trim() || null,
    company_name: formData.company_name.trim(),
    company_address: formData.company_address.trim() || null,
    employment_start: formData.employment_start,
    employment_end: formData.is_current_employment ? null : formData.employment_end || null,
    is_current_employment: formData.is_current_employment,
    duties_summary: formData.duties_summary.trim() || null,
    letter_place: formData.letter_place.trim() || null,
    letter_date: formData.letter_date || null,
    signer_name_title: formData.signer_name_title.trim() || null,
    locale: language === "fr" ? "fr" : "en",
  });

  const handleSave = async () => {
    if (!formData.title.trim() || !formData.employee_name.trim() || !formData.company_name.trim()) {
      toast.error("Title, employee name, and company name are required.");
      return;
    }
    if (!formData.employment_start) {
      toast.error("Employment start date is required.");
      return;
    }
    if (!formData.is_current_employment && !formData.employment_end) {
      toast.error("End date is required unless you still work there.");
      return;
    }

    setIsSaving(true);
    try {
      const payload = buildPayload();
      let response;
      if (isEdit) {
        response = await updateWorkCertificate(id, payload);
      } else {
        response = await createWorkCertificate(payload);
      }
      if (response.data.status) {
        toast.success(strings.messages?.saveSuccess || "Saved");
        if (!isEdit && response.data.data?.id) {
          navigate(`/work-certificate/edit/${response.data.data.id}`);
        }
      }
    } catch (error) {
      const msg = error.response?.data?.message;
      const errs = error.response?.data?.errors;
      if (errs) {
        const first = Object.values(errs)[0];
        toast.error(Array.isArray(first) ? first[0] : msg || "Validation error");
      } else {
        toast.error(msg || strings.messages?.saveError || "Failed to save");
      }
    } finally {
      setIsSaving(false);
    }
  };

  const handleDownload = async () => {
    if (!isEdit) {
      toast.error("Save the certificate first.");
      return;
    }
    setIsDownloading(true);
    try {
      const response = await downloadWorkCertificatePdf(id, language);
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute("download", `${(formData.title || "work-certificate").replace(/\s+/g, "_")}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch {
      toast.error(strings.messages?.pdfError || "PDF failed");
    } finally {
      setIsDownloading(false);
    }
  };

  if (isLoading) {
    return (
      <AuthLayout>
        <div className="flex items-center justify-center min-h-screen">
          <Loader2 className="h-10 w-10 animate-spin text-blue-600" />
        </div>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout>
      <div className="min-h-screen bg-slate-50">
        <div className="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-slate-200">
          <div className="max-w-[1600px] mx-auto px-4 h-20 flex items-center justify-between gap-3">
            <div className="flex items-center gap-3 min-w-0">
              <button
                type="button"
                onClick={() => navigate("/work-certificates")}
                className="p-2 rounded-full hover:bg-slate-100 text-slate-600 shrink-0"
              >
                <ArrowLeft className="h-6 w-6" />
              </button>
              <div className="min-w-0">
                <p className="text-xs text-slate-500 flex items-center gap-1 font-medium uppercase tracking-wider">
                  <ScrollText className="h-3.5 w-3.5 shrink-0" />
                  {strings.title}
                </p>
                <h1 className="text-lg sm:text-xl font-bold text-slate-900 truncate">
                  {formData.title || "—"}
                </h1>
              </div>
            </div>
            <div className="flex items-center gap-2 shrink-0">
              <button
                type="button"
                onClick={handleDownload}
                disabled={!isEdit || isDownloading}
                className="hidden sm:inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl font-semibold hover:bg-slate-50 text-sm disabled:opacity-50"
              >
                {isDownloading ? (
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                ) : (
                  <Download className="h-4 w-4 mr-2" />
                )}
                {t.common?.download || "Download"}
              </button>
              <button
                type="button"
                onClick={handleSave}
                disabled={isSaving}
                className="inline-flex items-center px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 text-sm shadow-lg shadow-blue-500/25 disabled:opacity-60"
              >
                {isSaving ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Save className="h-4 w-4 mr-2" />}
                {t.common?.save || "Save"}
              </button>
            </div>
          </div>
        </div>

        <div className="max-w-[1600px] mx-auto px-4 py-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {/* Editor */}
            <div className="space-y-6 animate-slide-in">
              <p className="text-sm text-slate-600">{strings.subtitle}</p>

              <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="p-6 border-b border-slate-100 bg-slate-50/50">
                  <div className="flex items-center gap-2">
                    <div className="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                      <ScrollText className="h-4 w-4 text-indigo-600" />
                    </div>
                    <h2 className="font-bold text-slate-900">{strings.editorSectionTitle || "Certificate details"}</h2>
                  </div>
                </div>
                <div className="p-6 space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-slate-700">{strings.form?.documentTitle}</label>
                    <input name="title" value={formData.title} onChange={handleChange} className={inputClass} />
                  </div>
                  <div className="grid sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-slate-700">{strings.form?.employeeName}</label>
                      <input name="employee_name" value={formData.employee_name} onChange={handleChange} className={inputClass} />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-slate-700">{strings.form?.jobTitle}</label>
                      <input
                        name="employee_job_title"
                        value={formData.employee_job_title}
                        onChange={handleChange}
                        className={inputClass}
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700">{strings.form?.companyName}</label>
                    <input name="company_name" value={formData.company_name} onChange={handleChange} className={inputClass} />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700">{strings.form?.companyAddress}</label>
                    <textarea
                      name="company_address"
                      rows={3}
                      value={formData.company_address}
                      onChange={handleChange}
                      className={inputClass}
                    />
                  </div>
                  <div className="grid sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-slate-700">{strings.form?.employmentStart}</label>
                      <input
                        type="date"
                        name="employment_start"
                        value={formData.employment_start}
                        onChange={handleChange}
                        className={inputClass}
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-slate-700">{strings.form?.employmentEnd}</label>
                      <input
                        type="date"
                        name="employment_end"
                        value={formData.employment_end}
                        onChange={handleChange}
                        disabled={formData.is_current_employment}
                        className={`${inputClass} disabled:bg-slate-100 disabled:text-slate-500`}
                      />
                    </div>
                  </div>
                  <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input
                      type="checkbox"
                      name="is_current_employment"
                      checked={formData.is_current_employment}
                      onChange={handleChange}
                      className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    />
                    {strings.form?.currentJob}
                  </label>
                  <div>
                    <label className="block text-sm font-medium text-slate-700">{strings.form?.duties}</label>
                    <textarea
                      name="duties_summary"
                      rows={4}
                      value={formData.duties_summary}
                      onChange={handleChange}
                      className={inputClass}
                    />
                  </div>
                  <div className="grid sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-slate-700">{strings.form?.letterPlace}</label>
                      <input name="letter_place" value={formData.letter_place} onChange={handleChange} className={inputClass} />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-slate-700">{strings.form?.letterDate}</label>
                      <input type="date" name="letter_date" value={formData.letter_date} onChange={handleChange} className={inputClass} />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700">{strings.form?.signer}</label>
                    <input
                      name="signer_name_title"
                      value={formData.signer_name_title}
                      onChange={handleChange}
                      className={inputClass}
                    />
                  </div>
                </div>
              </div>

              <button
                type="button"
                onClick={handleDownload}
                disabled={!isEdit || isDownloading}
                className="sm:hidden w-full inline-flex justify-center items-center gap-2 px-4 py-3 rounded-xl border border-slate-200 bg-white font-semibold text-slate-700 disabled:opacity-50"
              >
                {isDownloading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Download className="h-4 w-4" />}
                {t.common?.download || "Download PDF"}
              </button>
            </div>

            {/* Preview — same pattern as cover letter editor */}
            <div className="hidden lg:block sticky top-28 h-[calc(100vh-140px)]">
              <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                {strings.preview?.panelTitle || "Preview"}
              </p>
              <div className="bg-slate-200 rounded-3xl h-[calc(100%-1.5rem)] shadow-inner relative overflow-hidden flex flex-col items-center justify-center p-6 group">
                <div className="bg-white w-full h-full max-w-[600px] shadow-2xl rounded-sm p-10 sm:p-12 overflow-y-auto scrollbar-hide text-slate-800 leading-relaxed text-[11pt]">
                  <WorkCertificatePreviewBody formData={formData} localeKey={appLocaleKey} />
                </div>

                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm pointer-events-none">
                  <div className="bg-white p-4 rounded-2xl shadow-xl flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                      <LayoutIcon className="h-5 w-5 text-indigo-600" />
                    </div>
                    <p className="font-bold text-slate-900">{strings.preview?.liveHint || "Live preview"}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthLayout>
  );
}
