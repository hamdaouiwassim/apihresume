import { useState, useEffect, lazy, Suspense } from "react";
import { useParams, Link } from "react-router-dom";
import { FileText, Loader2, Download, ArrowLeft } from "lucide-react";
import { viewSharedResume } from "../services/ShareableLinkService";
import { generatePDF } from "../services/PDFService";
import { toast } from "sonner";
import { useLanguage } from "../context/LanguageContext";
import { buildResumeTemplateData } from "../utils/resumeTemplateMapper";
import ResumePreviewSkeleton from "../components/ResumePreviewSkeleton";
import { deriveTemplateLayout } from "../utils/templateStyles";

const ResumeTemplatePreview = lazy(() => import("../components/ResumeTemplatePreview"));

export default function SharedResumeView() {
  const { token } = useParams();
  const { language, t } = useLanguage();
  const [resume, setResume] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const locale = language === "fr" ? "fr-FR" : "en-US";
  const localeCode = language === "fr" ? "fr" : "en";

  useEffect(() => {
    fetchSharedResume();
  }, [token]);

  const fetchSharedResume = async () => {
    try {
      setLoading(true);
      const response = await viewSharedResume(token);
      if (response.data.status) {
        setResume(response.data.data);
      } else {
        setError(response.data.message || "Failed to load resume");
      }
    } catch (err) {
      setError(err.response?.data?.message || "Link not found or expired");
    } finally {
      setLoading(false);
    }
  };

  const handleDownload = async () => {
    try {
      toast.loading("Generating PDF...", { id: "pdf-generation" });

      const filename = `${resume?.name || formData.full_name || "cv"}.pdf`;
      const resumePayload = buildResumeTemplateData(formData, locale, {
        present: t.preview?.present,
      });

      const response = await generatePDF({
        resume: resumePayload,
        filename,
        locale: localeCode,
      });

      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      toast.success("PDF downloaded successfully!", { id: "pdf-generation" });
    } catch (error) {
      toast.error(
        error.response?.data?.message || "Failed to generate PDF",
        { id: "pdf-generation" }
      );
    }
  };

  const formatResumeData = (resume) => {
    if (!resume) return {
      full_name: "",
      email: "",
      phone: "",
      location: "",
      job_title: "",
      professional_summary: "",
      avatar: "",
      experiences: [],
      educations: [],
      skills: [],
      hobbies: [],
      certificates: [],
      template_layout: "classic",
      template_id: null,
    };
    
    const basicInfo = resume.basic_info || {};
    const templateLayout = deriveTemplateLayout(resume.template);
    const templateId = resume.template_id || resume.template?.id || null;

    return {
      full_name: basicInfo.full_name || "",
      email: basicInfo.email || "",
      phone: basicInfo.phone || "",
      location: basicInfo.location || "",
      job_title: basicInfo.job_title || "",
      professional_summary: basicInfo.professional_summary || "",
      avatar: basicInfo.avatar || "",
      linkedin: basicInfo.linkedin || "",
      website: basicInfo.github || "",
      experiences: resume.experiences || [],
      educations: resume.educations || [],
      skills: resume.skills || [],
      hobbies: resume.hobbies || [],
      certificates: resume.certificates || [],
      template_layout: templateLayout,
      template_id: templateId,
    };
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 animate-spin text-blue-600 mx-auto mb-4" />
          <p className="text-gray-600">Loading resume...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center max-w-md mx-auto p-6">
          <FileText className="h-16 w-16 text-red-500 mx-auto mb-4" />
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Link Invalid or Expired</h1>
          <p className="text-gray-600 mb-6">{error}</p>
          <Link
            to="/"
            className="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <ArrowLeft className="h-5 w-5 mr-2" />
            Go to Home
          </Link>
        </div>
      </div>
    );
  }

  const formData = formatResumeData(resume);
  const previewData = buildResumeTemplateData(formData, locale, {
    present: t.preview?.present,
  });

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="mb-8 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="p-2 bg-blue-100 rounded-lg">
              <FileText className="h-6 w-6 text-blue-600" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">{resume.name}</h1>
              <p className="text-sm text-gray-500">Shared Resume</p>
            </div>
          </div>
          <button
            onClick={handleDownload}
            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-lg"
          >
            <Download className="h-5 w-5 mr-2" />
            Download PDF
          </button>
        </div>

        {/* CV Preview */}
        <div className="bg-white rounded-lg shadow-lg p-8">
          <Suspense fallback={<ResumePreviewSkeleton />}>
            <ResumeTemplatePreview
              resume={previewData}
              templateKey={formData.template_layout}
            />
          </Suspense>
        </div>
      </div>
    </div>
  );
}

