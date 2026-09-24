import { useState, useEffect, useRef } from "react";
import { Experience } from "../Models/Experience";
import { useLanguage } from "../context/LanguageContext";
import {
  Save,
  Loader2,
  X,
} from "lucide-react";
import { storeExperience, updateExperience } from "../services/ExperienceService";
import { toast } from "sonner";
import EnhanceTextareaButton from "./EnhanceTextareaButton";

export default function NewExperience({exp = {
        company : "",
        description : "",
        startDate : null,
        endDate : null,
        position : "",
        is_present : false
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

const validateExperience = () => {
    const newErrors = {};
    if (!experience.company || experience.company.trim() === '') {
        newErrors.company = ['Company is required'];
    }
    if (!experience.position || experience.position.trim() === '') {
        newErrors.position = ['Position is required'];
    }
    return newErrors;
};

const hasValidationErrors = () => {
    const validationErrors = validateExperience();
    return Object.keys(validationErrors).length > 0 || Object.keys(errors).length > 0;
};

const handleStoreExperience = async () => {
    const validationErrors = validateExperience();
    if (Object.keys(validationErrors).length > 0) {
        setErrors(validationErrors);
        return;
    }
   
    setIsLoading(true);
    let data = {...experience , resume_id : resumeId }
    try{
    const response = await storeExperience(data);
    closeForm();
    toast.success(response.data.message || "");
    if (onSave) onSave();
    }catch(error) {
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors);
      }
      toast.error("Error saving experience. Please try again.");
    } finally {
      setIsLoading(false);
    }

  }
const handleUpdateExperience = async () => {
    const validationErrors = validateExperience();
    if (Object.keys(validationErrors).length > 0) {
        setErrors(validationErrors);
        return;
    }
    setIsLoading(true);
let data = {...experience }
    try{
    const response = await updateExperience(data,exp.id);
    closeForm();
    toast.success(response.data.message || "");
    if (onSave) onSave();
    }catch(error) {
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors);
      }
      toast.error("Error updating experience. Please try again.");
    } finally {
      setIsLoading(false);
    }
}
    const [experience,setExperience] = useState(exp);

    const previewChangeRef = useRef(onPreviewChange);
    useEffect(() => {
      previewChangeRef.current = onPreviewChange;
    }, [onPreviewChange]);

    useEffect(() => {
      previewChangeRef.current?.(experience);
    }, [experience]);

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
                      className="bg-gradient-to-br from-amber-50/50 to-orange-50/30 border border-amber-200/60 rounded-2xl p-6 space-y-5 shadow-sm"
                    >
                      <div className="flex justify-between items-center">
                        <h4 className="text-base font-semibold text-slate-800">
                          {edit ? "Edit Experience" : `Experience ${index !== null ? `#${index + 1}` : ''}`}
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
                            { t.dashboard.sections.experiences.experience.company }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ t.dashboard.sections.experiences.experience.companyHint })
                            </span>
                          </label>
                          <div className="relative">
                            <input
                              type="text"
                              value={experience.company}
                              onChange={(e) => {
                                setExperience({...experience, company: e.target.value});
                                if (errors.company) {
                                    const newErrors = {...errors};
                                    delete newErrors.company;
                                    setErrors(newErrors);
                                }
                              }}
                              className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                errors.company ? 'border-red-500 ring-red-200' : ''
                              }`}
                              placeholder={ t.dashboard.sections.experiences.experience.companyPlaceholder }
                            />
                            {errors.company && (
                                <p className="mt-1.5 text-sm text-red-600">{Array.isArray(errors.company) ? errors.company[0] : errors.company}</p>
                            )}
                          </div>
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            { t.dashboard.sections.experiences.experience.position }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ t.dashboard.sections.experiences.experience.positionHint })
                            </span>
                          </label>
                          <div className="relative">
                            <input
                              type="text"
                              value={experience.position}
                              onChange={(e) => {
                                setExperience({...experience, position: e.target.value});
                                if (errors.position) {
                                    const newErrors = {...errors};
                                    delete newErrors.position;
                                    setErrors(newErrors);
                                }
                              }}
                              className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                errors.position ? 'border-red-500 ring-red-200' : ''
                              }`}
                              placeholder={ t.dashboard.sections.experiences.experience.positionPlaceholder }
                            />
                            {errors.position && (
                                <p className="mt-1.5 text-sm text-red-600">{Array.isArray(errors.position) ? errors.position[0] : errors.position}</p>
                            )}
                          </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-2">
                              { t.dashboard.sections.experiences.experience.startDate }{" "}
                              <span className="text-slate-400 font-normal text-xs">
                                ({ t.dashboard.sections.experiences.experience.startDateHint })
                              </span>
                            </label>
                            <input
                              type="date"
                              value={experience.startDate}
                              onChange={(e) =>
                                setExperience(
                                  {...experience, startDate :e.target.value}
                                  
                                )
                              }
                              className="w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-2">
                              { t.dashboard.sections.experiences.experience.endDate }{" "}
                              <span className="text-slate-400 font-normal text-xs">
                                ({ t.dashboard.sections.experiences.experience.endDateHint })
                              </span>
                            </label>
                            <input
                              type="date"
                              value={experience.endDate || ""}
                              onChange={(e) =>
                                setExperience(
                                  {...experience, endDate :e.target.value}
                                  
                                )
                              }
                              disabled={experience.is_present}
                              className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                experience.is_present ? 'opacity-50 cursor-not-allowed bg-slate-50' : ''
                              }`}
                            />
                          </div>
                        </div>

                        <div className="flex items-center gap-3">
                          <input
                            type="checkbox"
                            id={`is-present-${index}`}
                            checked={experience.is_present || false}
                            onChange={(e) => {
                              setExperience({
                                ...experience,
                                is_present: e.target.checked,
                                endDate: e.target.checked ? null : experience.endDate
                              });
                            }}
                            className="w-4 h-4 text-blue-600 bg-white border-slate-300 rounded focus:ring-blue-500 focus:ring-2"
                          />
                          <label htmlFor={`is-present-${index}`} className="text-sm font-semibold text-slate-700 cursor-pointer">
                            { t.dashboard.sections.experiences.experience.isPresent }
                          </label>
                        </div>

                        <div>
                          <label className="block text-sm font-semibold text-slate-700 mb-2">
                            { t.dashboard.sections.experiences.experience.description }{" "}
                            <span className="text-slate-400 font-normal text-xs">
                              ({ t.dashboard.sections.experiences.experience.descriptionHint })
                            </span>
                          </label>
                          <textarea
                            rows={4}
                            value={experience.description}
                            onChange={(e) =>
                                setExperience(
                                  {...experience, description :e.target.value}
                                  
                                )
                              }
                            className="w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm p-4 resize-none"
                            placeholder={ t.dashboard.sections.experiences.experience.descriptionPlaceholder }
                          />
                          <div className="mt-2 flex justify-end">
                            <EnhanceTextareaButton
                              value={experience.description || ""}
                              context="resume experience achievement bullet points"
                              onEnhanced={(enhanced) =>
                                setExperience({ ...experience, description: enhanced })
                              }
                            />
                          </div>
                        </div>

                        <div className="flex justify-end pt-2">
                    <button 
                        type="button"
                        onClick={ ()=> { !edit ? handleStoreExperience(index) : handleUpdateExperience(index) } } 
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
