import { useState, useEffect, useRef } from "react";
import { useLanguage } from "../context/LanguageContext";
import {
  Save,
  Loader2,
  X
} from "lucide-react";
import { storeLanguage, updateLanguage } from "../services/LanguageService";
import { toast } from "sonner";

export default function NewLanguage({lang = {
        language : "",
        proficiency : ""
    } ,index = null  , hide , resumeId , edit=false , onSave, onPreviewChange, onPreviewClear}) {
const { t } = useLanguage();
const [errors,setErrors]=useState({});
const [isLoading, setIsLoading] = useState(false);

const buttonBase = "inline-flex items-center gap-2 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2";
const buttonVariants = {
  primary: `${buttonBase} px-5 py-2.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white shadow-lg hover:shadow-xl focus:ring-blue-500 focus:ring-offset-white`,
  danger: `${buttonBase} px-4 py-2 border border-red-200 text-red-600 bg-white hover:border-red-300 hover:bg-red-50 focus:ring-red-400 focus:ring-offset-white`,
};
const disabledButtonClasses = "opacity-50 cursor-not-allowed pointer-events-none";

const validateLanguage = () => {
    const newErrors = {};
    if (!language.language || language.language.trim() === '') {
        newErrors.language = ['Language is required'];
    }
    return newErrors;
};

const hasValidationErrors = () => {
    const validationErrors = validateLanguage();
    return Object.keys(validationErrors).length > 0 || Object.keys(errors).length > 0;
};

const handleStoreLanguage = async () => {
    const validationErrors = validateLanguage();
    if (Object.keys(validationErrors).length > 0) {
        setErrors(validationErrors);
        return;
    }
   
    setIsLoading(true);
    let data = {...language , resume_id : resumeId }
    try{
    const response = await storeLanguage(data);
    closeForm();
    toast.success(response.data.message || "");
    if (onSave) onSave();
    }catch(error) {
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors);
      }
      toast.error("Error saving language. Please try again.");
    } finally {
      setIsLoading(false);
    }

  }
const handleUpdateLanguage = async () => {
    const validationErrors = validateLanguage();
    if (Object.keys(validationErrors).length > 0) {
        setErrors(validationErrors);
        return;
    }
    setIsLoading(true);
    let data = {...language }
    try{
    const response = await updateLanguage(data,lang.id);
    closeForm();
    toast.success(response.data.message || "");
    if (onSave) onSave();
    }catch(error) {
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors);
      }
      toast.error("Error updating language. Please try again.");
    } finally {
      setIsLoading(false);
    }
}
    const [language,setLanguage] = useState(lang);

    const previewChangeRef = useRef(onPreviewChange);
    useEffect(() => {
      previewChangeRef.current = onPreviewChange;
    }, [onPreviewChange]);

    useEffect(() => {
      previewChangeRef.current?.(language);
    }, [language]);

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
                      className="bg-gradient-to-br from-purple-50/50 to-indigo-50/30 border border-purple-200/60 rounded-2xl p-6 space-y-5 shadow-sm"
                    >
                      <div className="flex justify-between items-center">
                        <h4 className="text-base font-semibold text-slate-800">
                          {edit ? "Edit Language" : `Language ${index !== null ? `#${index + 1}` : ''}`}
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
                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            { t.dashboard.sections.languages.language.language }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ t.dashboard.sections.languages.language.languageHint })
                            </span>
                          </label>
                          <div className="relative">
                            <input
                              type="text"
                              value={language.language}
                              onChange={(e) => {
                                setLanguage({...language, language: e.target.value});
                                if (errors.language) {
                                    const newErrors = {...errors};
                                    delete newErrors.language;
                                    setErrors(newErrors);
                                }
                              }}
                              className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                errors.language ? 'border-red-500 ring-red-200' : ''
                              }`}
                              placeholder={ t.dashboard.sections.languages.language.languagePlaceholder }
                            />
                            {errors.language && (
                                <p className="mt-1.5 text-sm text-red-600">{Array.isArray(errors.language) ? errors.language[0] : errors.language}</p>
                            )}
                          </div>
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            { t.dashboard.sections.languages.language.proficiency }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ t.dashboard.sections.languages.language.proficiencyHint })
                            </span>
                          </label>
                          <div className="relative">
                            <select
                              value={language.proficiency}
                              onChange={(e) => {
                                setLanguage({...language, proficiency: e.target.value});
                                if (errors.proficiency) {
                                    const newErrors = {...errors};
                                    delete newErrors.proficiency;
                                    setErrors(newErrors);
                                }
                              }}
                              className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                errors.proficiency ? 'border-red-500 ring-red-200' : ''
                              }`}
                            >
                              <option value="">{ t.dashboard.sections.languages.language.selectProficiency }</option>
                              <option value="Native">{ t.dashboard.sections.languages.language.native }</option>
                              <option value="Fluent">{ t.dashboard.sections.languages.language.fluent }</option>
                              <option value="Intermediate">{ t.dashboard.sections.languages.language.intermediate }</option>
                              <option value="Basic">{ t.dashboard.sections.languages.language.basic }</option>
                            </select>
                            {errors.proficiency && (
                                <p className="mt-1.5 text-sm text-red-600">{Array.isArray(errors.proficiency) ? errors.proficiency[0] : errors.proficiency}</p>
                            )}
                          </div>
                        </div>

                        <div className="flex justify-end pt-2">
                    <button
                        type="button"
                        onClick={ ()=> { !edit ? handleStoreLanguage(index) : handleUpdateLanguage(index) } } 
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

