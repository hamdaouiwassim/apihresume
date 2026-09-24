import { useState, useRef, useCallback } from "react";
import { useLanguage } from "../context/LanguageContext";
import { Upload, FileText, Loader2, X, AlertCircle, CheckCircle2 } from "lucide-react";
import { extractTextFromPdf } from "../utils/cvPdfParser";
import { parseCvText as aiParseCvText } from "../services/aiService";

export default function ImportCvModal({ onClose, onImport }) {
  const { t } = useLanguage();
  const [file, setFile] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [parsedData, setParsedData] = useState(null);
  const [error, setError] = useState("");
  const [dragOver, setDragOver] = useState(false);
  const fileInputRef = useRef(null);

  const handleFileDrop = useCallback((e) => {
    e.preventDefault();
    setDragOver(false);
    const droppedFile = e.dataTransfer?.files?.[0];
    if (droppedFile && droppedFile.type === "application/pdf") {
      setFile(droppedFile);
      setError("");
      setParsedData(null);
    } else if (droppedFile) {
      setError("Please select a PDF file.");
    }
  }, []);

  const handleFileSelect = (e) => {
    const selectedFile = e.target.files?.[0];
    if (selectedFile) {
      setFile(selectedFile);
      setError("");
      setParsedData(null);
    }
  };

  const handleProcess = async () => {
    if (!file) return;
    setIsProcessing(true);
    setError("");
    try {
      const text = await extractTextFromPdf(file);
      const truncated = text.length > 5000 ? text.slice(0, 5000) + "\n\n[TRUNCATED]" : text;
      const response = await aiParseCvText({ text: truncated });
      const data = response.data?.data;
      if (!data) {
        setError("AI returned an empty response. Please try again.");
        return;
      }
      setParsedData(data);
    } catch (err) {
      const status = err.response?.status;
      const serverMsg = err.response?.data?.message;
      if (serverMsg) {
        setError(serverMsg);
      } else if (err.code === 'ECONNABORTED') {
        setError('Request timed out. The AI service took too long to respond. Please try again with a shorter CV.');
      } else if (!err.response) {
        setError('Network error — could not reach the server. Check your connection and try again.');
      } else {
        setError(`Unexpected error (HTTP ${status || '?'}). Please try again later.`);
      }
      console.error('ImportCvModal parse error', err);
    } finally {
      setIsProcessing(false);
    }
  };

  const sections = parsedData ? [
    { key: "full_name", label: "Full Name", value: parsedData.full_name },
    { key: "email", label: "Email", value: parsedData.email },
    { key: "phone", label: "Phone", value: parsedData.phone },
    { key: "location", label: "Location", value: parsedData.location },
    { key: "job_title", label: "Job Title", value: parsedData.job_title },
    { key: "linkedin", label: "LinkedIn", value: parsedData.linkedin },
    { key: "github", label: "GitHub", value: parsedData.github },
    { key: "professional_summary", label: "Summary", value: parsedData.professional_summary },
    { key: "experiences", label: "Experiences", value: parsedData.experiences.length + " entries" },
    { key: "educations", label: "Educations", value: parsedData.educations.length + " entries" },
    { key: "skills", label: "Skills", value: parsedData.skills.length + " items" },
    { key: "languages", label: "Languages", value: parsedData.languages.length + " items" },
    { key: "certificates", label: "Certificates", value: parsedData.certificates.length + " items" },
    { key: "projects", label: "Projects", value: parsedData.projects.length + " items" },
    { key: "hobbies", label: "Hobbies", value: parsedData.hobbies.length + " items" },
  ] : [];

  return (
    <div
      className="fixed inset-0 z-[100] overflow-y-auto bg-gray-900/75"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
    >
      <div className="flex min-h-full items-center justify-center p-4">
        <div
          className="relative w-full max-w-xl my-8 rounded-2xl bg-white shadow-xl"
          onClick={(e) => e.stopPropagation()}
        >
          <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h2 className="text-lg font-semibold text-slate-800">Import CV from PDF</h2>
            <button
              type="button"
              onClick={onClose}
              className="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
            >
              <X className="h-5 w-5" />
            </button>
          </div>

          <div className="px-6 py-5 space-y-5">
            {!parsedData && (
              <div
                onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                onDragLeave={() => setDragOver(false)}
                onDrop={handleFileDrop}
                onClick={() => fileInputRef.current?.click()}
                className={`border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors ${
                  dragOver
                    ? "border-blue-500 bg-blue-50"
                    : "border-slate-300 hover:border-blue-400 hover:bg-slate-50"
                }`}
              >
                <Upload className="h-10 w-10 text-slate-400 mx-auto mb-3" />
                <p className="text-sm text-slate-600 font-medium mb-1">
                  Drop your CV PDF here or click to browse
                </p>
                <p className="text-xs text-slate-400">PDF files only</p>
                <input
                  ref={fileInputRef}
                  type="file"
                  accept=".pdf,application/pdf"
                  className="hidden"
                  onChange={handleFileSelect}
                />
              </div>
            )}

            {file && !parsedData && (
              <div className="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                <FileText className="h-5 w-5 text-blue-600" />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-slate-700 truncate">{file.name}</p>
                  <p className="text-xs text-slate-500">{(file.size / 1024).toFixed(1)} KB</p>
                </div>
                <button
                  type="button"
                  onClick={() => { setFile(null); setError(""); }}
                  className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>
            )}

            {error && (
              <div className="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                <AlertCircle className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
                <p className="text-sm text-red-700">{error}</p>
              </div>
            )}

            {parsedData && (
              <div>
                <div className="flex items-center gap-2 mb-3">
                  <CheckCircle2 className="h-5 w-5 text-emerald-500" />
                  <h3 className="text-sm font-semibold text-slate-800">Parsed Data Preview</h3>
                </div>
                <div className="divide-y divide-slate-100 border border-slate-200 rounded-lg max-h-72 overflow-y-auto">
                  {sections.filter(s => s.value).map((s) => (
                    <div key={s.key} className="flex items-center justify-between px-4 py-2.5">
                      <span className="text-sm text-slate-600">{s.label}</span>
                      <span className="text-sm font-medium text-slate-800 truncate max-w-[60%] text-right">{s.value}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          <div className="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors"
            >
              {t.common?.cancel || "Cancel"}
            </button>
            {parsedData ? (
              <button
                type="button"
                onClick={() => onImport(parsedData)}
                className="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-xl hover:shadow-lg transition-all"
              >
                Apply to My CV
              </button>
            ) : (
              <button
                type="button"
                onClick={handleProcess}
                disabled={!file || isProcessing}
                className={`px-5 py-2 text-sm font-semibold rounded-xl transition-all flex items-center gap-2 ${
                  !file || isProcessing
                    ? "bg-slate-200 text-slate-400 cursor-not-allowed"
                    : "text-white bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:shadow-lg"
                }`}
              >
                {isProcessing ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Parsing...
                  </>
                ) : (
                  "Parse PDF"
                )}
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
