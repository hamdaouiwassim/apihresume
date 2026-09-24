import { useState, useEffect, useRef, useContext } from "react";
import { useLanguage } from "../context/LanguageContext";
import {
  Save,
  Loader2,
  X,
  Github,
} from "lucide-react";
import { storeProject, updateProject, previewGitHubProject } from "../services/ProjectService";
import { getGitHubImportUrl, prepareSpaRequest } from "../services/authService";
import { AuthContext } from "../context/AuthContext";
import { toast } from "sonner";
import EnhanceTextareaButton from "./EnhanceTextareaButton";

export default function NewProject({ project = {
    name: "",
    description: "",
    technologies: "",
    url: "",
    startDate: null,
    endDate: null,
    experience_id: null,
}, index = null, hide, resumeId, experiences = [], edit = false, onSave, onPreviewChange, onPreviewClear }) {
const { t } = useLanguage();
const { user } = useContext(AuthContext);
const projectStrings = t.dashboard?.sections?.projects?.project || {};
const [errors,setErrors]=useState({});
const [isLoading, setIsLoading] = useState(false);
const [githubRepoInput, setGithubRepoInput] = useState("");
const [githubImportLoading, setGithubImportLoading] = useState(false);
const [githubConnectLoading, setGithubConnectLoading] = useState(false);
const [projectState,setProject] = useState(project);

const buttonBase = "inline-flex items-center gap-2 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2";
const buttonVariants = {
  primary: `${buttonBase} px-5 py-2.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white shadow-lg hover:shadow-xl focus:ring-blue-500 focus:ring-offset-white`,
  danger: `${buttonBase} px-4 py-2 border border-red-200 text-red-600 bg-white hover:border-red-300 hover:bg-red-50 focus:ring-red-400 focus:ring-offset-white`,
};
const disabledButtonClasses = "opacity-50 cursor-not-allowed pointer-events-none";

const validateProject = (projectData) => {
    const newErrors = {};
    if (!projectData.name || projectData.name.trim() === '') {
        newErrors.name = ['Project name is required'];
    }
    return newErrors;
};

const hasValidationErrors = () => {
    const validationErrors = validateProject(projectState);
    return Object.keys(validationErrors).length > 0 || Object.keys(errors).length > 0;
};

const handleGithubImport = async () => {
    const url = githubRepoInput.trim();
    if (!url) {
      toast.error(projectStrings.githubRepoRequired || "Enter a GitHub repository URL first.");
      return;
    }
    setGithubImportLoading(true);
    try {
      const body = { repo_url: url };
      if (projectState.experience_id) {
        body.experience_id = Number(projectState.experience_id);
      }
      const response = await previewGitHubProject(resumeId, body);
      const d = response.data?.data;
      if (!d) {
        throw new Error("empty");
      }
      setProject((prev) => ({
        ...prev,
        name: d.name ?? prev.name,
        description: d.description ?? "",
        technologies: d.technologies ?? "",
        url: d.url ?? prev.url ?? "",
        startDate: d.startDate ?? prev.startDate ?? null,
        endDate: d.endDate ?? prev.endDate ?? null,
        experience_id: d.experience_id != null ? d.experience_id : prev.experience_id,
      }));
      setErrors({});
      toast.success(projectStrings.githubImportSuccess || "Updated from GitHub.");
    } catch (error) {
      const msg = error.response?.data?.message;
      toast.error(msg || projectStrings.githubImportError || "GitHub import failed.");
    } finally {
      setGithubImportLoading(false);
    }
};

const handleConnectGitHubForImport = async () => {
    if (!resumeId) {
      toast.error(projectStrings.githubImportError || "GitHub import failed.");
      return;
    }
    setGithubConnectLoading(true);
    try {
      await prepareSpaRequest(true);
      const returnTo = `/resume/edit/${resumeId}`;
      const { data } = await getGitHubImportUrl({ params: { return_to: returnTo } });
      if (data?.status && data?.url) {
        window.location.href = data.url;
        return;
      }
      toast.error(data?.message || projectStrings.githubConnectUnavailable || "GitHub connection is not configured.");
    } catch (error) {
      toast.error(error.response?.data?.message || projectStrings.githubImportError || "GitHub import failed.");
    } finally {
      setGithubConnectLoading(false);
    }
};

const handleStoreProject = async () => {
    const validationErrors = validateProject(projectState);
    if (Object.keys(validationErrors).length > 0) {
        setErrors(validationErrors);
        return;
    }
   
    setIsLoading(true);
    const payload = { ...projectState, resume_id: resumeId };
    if (payload.experience_id === "" || payload.experience_id === undefined) payload.experience_id = null;
    try {
    const response = await storeProject(payload);
    closeForm();
    toast.success(response.data.message || "");
    if (onSave) onSave();
    }catch(error) {
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors);
      }
      toast.error("Error saving project. Please try again.");
    } finally {
      setIsLoading(false);
    }

  }
const handleUpdateProject = async () => {
    const validationErrors = validateProject(projectState);
    if (Object.keys(validationErrors).length > 0) {
        setErrors(validationErrors);
        return;
    }
    setIsLoading(true);
    const payload = { ...projectState };
    if (payload.experience_id === "" || payload.experience_id === undefined) payload.experience_id = null;
    try {
    const response = await updateProject(payload, projectState.id);
    closeForm();
    toast.success(response.data.message || "");
    if (onSave) onSave();
    }catch(error) {
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors);
      }
      toast.error("Error updating project. Please try again.");
    } finally {
      setIsLoading(false);
    }
}

    const previewChangeRef = useRef(onPreviewChange);
    useEffect(() => {
      previewChangeRef.current = onPreviewChange;
    }, [onPreviewChange]);

    useEffect(() => {
      previewChangeRef.current?.(projectState);
    }, [projectState]);

    const previewClearRef = useRef(onPreviewClear);
    useEffect(() => {
      previewClearRef.current = onPreviewClear;
    }, [onPreviewClear]);

    useEffect(() => {
      return () => {
        previewClearRef.current?.();
      };
    }, []);

    const closeForm = () => {
      previewClearRef.current?.();
      hide && hide();
    };

    return (
            <div
                      key={index}
                      className="bg-gradient-to-br from-purple-50/50 to-pink-50/30 border border-purple-200/60 rounded-2xl p-6 space-y-5 shadow-sm"
                    >
                      <div className="flex justify-between items-center">
                        <h4 className="text-base font-semibold text-slate-800">
                          {edit ? "Edit Project" : `Project ${index !== null ? `#${index + 1}` : ''}`}
                        </h4>
                        <button
                          type="button"
                          onClick={closeForm}
                          className={`${buttonVariants.danger} p-2`}
                          title="Cancel"
                        >
                          <X className="h-4 w-4" />
                        </button>
                      </div>

                      <div className="grid grid-cols-1 gap-5">
                        {experiences && experiences.length > 0 && (
                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            {projectStrings.underExperience ?? "Under experience"}{" "}
                            <span className="text-slate-400 font-normal text-xs">(optional)</span>
                          </label>
                          <select
                            value={projectState.experience_id ?? ""}
                            onChange={(e) => setProject({ ...projectState, experience_id: e.target.value || null })}
                            className="w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3"
                          >
                            <option value="">— None (standalone project) —</option>
                            {experiences.map((exp) => (
                              <option key={exp.id} value={exp.id}>
                                {exp.company || "Company"} – {exp.position || "Position"}
                              </option>
                            ))}
                          </select>
                        </div>
                        )}

                        <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-4 space-y-3">
                          <div className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <Github className="h-4 w-4 shrink-0 text-slate-700" aria-hidden />
                            {projectStrings.githubRepoLabel || "Import from GitHub"}
                          </div>
                          <p className="text-xs text-slate-600 leading-relaxed">
                            {projectStrings.githubRepoHint ||
                              "Paste a GitHub repository URL to pre-fill name, URL, description, technologies, and start date."}
                          </p>
                          {!user?.github_import_connected && (
                            <div className="flex flex-wrap items-center gap-2">
                              <button
                                type="button"
                                onClick={handleConnectGitHubForImport}
                                disabled={githubConnectLoading || !resumeId}
                                className={`text-xs font-semibold text-blue-700 hover:text-blue-900 underline-offset-2 hover:underline disabled:opacity-50 disabled:no-underline`}
                              >
                                {githubConnectLoading
                                  ? (t.common?.loading || "Loading")
                                  : (projectStrings.githubConnectFromEditor || "Connect GitHub")}
                              </button>
                              <span className="text-xs text-slate-500">
                                {projectStrings.githubConnectHintShort || "for private repos"}
                              </span>
                            </div>
                          )}
                          <div className="flex flex-col sm:flex-row gap-2">
                            <input
                              type="text"
                              value={githubRepoInput}
                              onChange={(e) => setGithubRepoInput(e.target.value)}
                              className="flex-1 min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50"
                              placeholder={projectStrings.githubRepoPlaceholder || "https://github.com/owner/repo"}
                              autoComplete="off"
                            />
                            <button
                              type="button"
                              onClick={handleGithubImport}
                              disabled={githubImportLoading || !resumeId}
                              className={`${buttonVariants.primary} shrink-0 justify-center px-4 py-2.5 text-sm ${
                                githubImportLoading || !resumeId ? disabledButtonClasses : ""
                              }`}
                            >
                              {githubImportLoading ? (
                                <>
                                  <Loader2 className="h-4 w-4 animate-spin shrink-0" />
                                  <span>{t.common?.loading || "Loading"}</span>
                                </>
                              ) : (
                                <>
                                  <Github className="h-4 w-4 shrink-0" />
                                  <span>{projectStrings.githubImportButton || "Fill from GitHub"}</span>
                                </>
                              )}
                            </button>
                          </div>
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            { projectStrings.name }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ projectStrings.nameHint })
                            </span>
                          </label>
                          <div className="relative">
                            <input
                              type="text"
                              value={projectState.name}
                              onChange={(e) => {
                                setProject({...projectState, name: e.target.value});
                                if (errors.name) {
                                    const newErrors = {...errors};
                                    delete newErrors.name;
                                    setErrors(newErrors);
                                }
                              }}
                              className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                errors.name ? 'border-red-500 ring-red-200' : ''
                              }`}
                              placeholder={ projectStrings.namePlaceholder }
                            />
                            {errors.name && (
                                <p className="mt-1.5 text-sm text-red-600">{Array.isArray(errors.name) ? errors.name[0] : errors.name}</p>
                            )}
                          </div>
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            { projectStrings.url }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ projectStrings.urlHint })
                            </span>
                          </label>
                          <div className="relative">
                            <input
                              type="url"
                              value={projectState.url || ""}
                              onChange={(e) => {
                                setProject({...projectState, url: e.target.value});
                                if (errors.url) {
                                    const newErrors = {...errors};
                                    delete newErrors.url;
                                    setErrors(newErrors);
                                }
                              }}
                              className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                errors.url ? 'border-red-500 ring-red-200' : ''
                              }`}
                              placeholder={ projectStrings.urlPlaceholder }
                            />
                            {errors.url && (
                                <p className="mt-1.5 text-sm text-red-600">{Array.isArray(errors.url) ? errors.url[0] : errors.url}</p>
                            )}
                          </div>
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            { projectStrings.technologies }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ projectStrings.technologiesHint })
                            </span>
                          </label>
                          <div className="relative">
                            <input
                              type="text"
                              value={projectState.technologies || ""}
                              onChange={(e) => {
                                setProject({...projectState, technologies: e.target.value});
                                if (errors.technologies) {
                                    const newErrors = {...errors};
                                    delete newErrors.technologies;
                                    setErrors(newErrors);
                                }
                              }}
                              className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                errors.technologies ? 'border-red-500 ring-red-200' : ''
                              }`}
                              placeholder={ projectStrings.technologiesPlaceholder }
                            />
                            {errors.technologies && (
                                <p className="mt-1.5 text-sm text-red-600">{Array.isArray(errors.technologies) ? errors.technologies[0] : errors.technologies}</p>
                            )}
                          </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-2">
                              { projectStrings.startDate }{" "}
                              <span className="text-slate-400 font-normal text-xs">
                                ({ projectStrings.startDateHint })
                              </span>
                            </label>
                            <input
                              type="date"
                              value={projectState.startDate || ""}
                              onChange={(e) =>
                                setProject(
                                  {...projectState, startDate :e.target.value}
                                  
                                )
                              }
                              className="w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-2">
                              { projectStrings.endDate }{" "}
                              <span className="text-slate-400 font-normal text-xs">
                                ({ projectStrings.endDateHint })
                              </span>
                            </label>
                            <input
                              type="date"
                              value={projectState.endDate || ""}
                              onChange={(e) =>
                                setProject(
                                  {...projectState, endDate :e.target.value}
                                  
                                )
                              }
                              className="w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3"
                            />
                          </div>
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            { projectStrings.description }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ projectStrings.descriptionHint })
                            </span>
                          </label>
                          <textarea
                            rows={4}
                            value={projectState.description || ""}
                            onChange={(e) =>
                                setProject(
                                  {...projectState, description :e.target.value}
                                  
                                )
                              }
                            className="w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm p-4 resize-none"
                            placeholder={ projectStrings.descriptionPlaceholder }
                          />
                          <div className="mt-2 flex justify-end">
                            <EnhanceTextareaButton
                              value={projectState.description || ""}
                              context="project description"
                              onEnhanced={(enhanced) =>
                                setProject({ ...projectState, description: enhanced })
                              }
                            />
                          </div>
                        </div>

                        <div className="flex justify-end pt-2">
                    <button 
                        type="button"
                        onClick={ ()=> { !edit ? handleStoreProject(index) : handleUpdateProject(index) } } 
                        disabled={hasValidationErrors() || isLoading}
                        className={`${buttonVariants.primary} ${
                            hasValidationErrors() || isLoading ? disabledButtonClasses : ""
                        }`}
                    >
                        {isLoading ? (
                            <>
                                <Loader2 className="h-4 w-4 animate-spin" /> 
                                <span>{t.common.loading}</span>
                            </>
                        ) : (
                            <>
                                <Save className="h-4 w-4" /> 
                                <span>{!edit ? t.common.save : t.common.update}</span>
                            </>
                        )}
                    </button>
                </div>
                      </div>
                    </div>
    )

}

