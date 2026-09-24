import { useState, useEffect, useRef } from "react";
import { useLanguage } from "../context/LanguageContext";
import {
  Save,
  Loader2,
  X
} from "lucide-react";
import { storeSkill, updateSkill } from "../services/SkillService";
import { toast } from "sonner";

export default function NewSkill({skill = {
    name: "",
    proficiency: ""
}, index = null, hide, resumeId, edit = false, onSave, onPreviewChange, onPreviewClear}) {
    const { t } = useLanguage();
    const [errors, setErrors] = useState({});
    const [isLoading, setIsLoading] = useState(false);

    const buttonBase = "inline-flex items-center gap-2 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2";
    const buttonVariants = {
      primary: `${buttonBase} px-5 py-2.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white shadow-lg hover:shadow-xl focus:ring-blue-500 focus:ring-offset-white`,
      danger: `${buttonBase} px-4 py-2 border border-red-200 text-red-600 bg-white hover:border-red-300 hover:bg-red-50 focus:ring-red-400 focus:ring-offset-white`,
    };
    const disabledButtonClasses = "opacity-50 cursor-not-allowed pointer-events-none";
    
    const validateSkill = () => {
        const newErrors = {};
        if (!skillData.name || skillData.name.trim() === '') {
            newErrors.name = ['Name is required'];
        }
        return newErrors;
    };

    const hasValidationErrors = () => {
        const validationErrors = validateSkill();
        return Object.keys(validationErrors).length > 0 || Object.keys(errors).length > 0;
    };
    
    const handleStoreSkill = async () => {
        const validationErrors = validateSkill();
        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            return;
        }
        setIsLoading(true);
        let data = {...skillData, resume_id: resumeId};
        try {
            const response = await storeSkill(data);
            closeForm();
            toast.success(response.data.message || "");
            if (onSave) onSave();
        } catch (error) {
            if (error.response && error.response.data && error.response.data.errors) {
                setErrors(error.response.data.errors);
            }
            toast.error("Error saving skill. Please try again.");
        } finally {
            setIsLoading(false);
        }
    };

    const handleUpdateSkill = async () => {
        const validationErrors = validateSkill();
        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            return;
        }
        setIsLoading(true);
        let data = {...skillData};
        try {
            const response = await updateSkill(data, skill.id);
            closeForm();
            toast.success(response.data.message || "");
            if (onSave) onSave();
        } catch (error) {
            if (error.response && error.response.data && error.response.data.errors) {
                setErrors(error.response.data.errors);
            }
            toast.error("Error updating skill. Please try again.");
        } finally {
            setIsLoading(false);
        }
    };

    const [skillData, setSkillData] = useState(skill);

    const previewChangeRef = useRef(onPreviewChange);
    useEffect(() => {
        previewChangeRef.current = onPreviewChange;
    }, [onPreviewChange]);

    useEffect(() => {
        previewChangeRef.current?.(skillData);
    }, [skillData]);

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
            className="bg-gradient-to-br from-emerald-50/50 to-teal-50/30 border border-emerald-200/60 rounded-2xl p-6 space-y-5 shadow-sm"
        >
            <div className="flex justify-between items-center">
                <h4 className="text-base font-semibold text-slate-800">
                    {edit ? "Edit Skill" : `${t.dashboard.sections.skills.skill.title} ${index !== null ? `#${index + 1}` : ''}`}
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
                        {t.dashboard.sections.skills.skill.name}{" "}
                        <span className="text-slate-400 font-normal text-xs">
                            ({t.dashboard.sections.skills.skill.nameHint})
                        </span>
                    </label>
                    <div className="relative">
                        <input
                            type="text"
                            value={skillData.name}
                            onChange={(e) => {
                                setSkillData({...skillData, name: e.target.value});
                                if (errors.name) {
                                    const newErrors = {...errors};
                                    delete newErrors.name;
                                    setErrors(newErrors);
                                }
                            }}
                            className={`w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3 ${
                                errors.name ? 'border-red-500 ring-red-200' : ''
                            }`}
                            placeholder={t.dashboard.sections.skills.skill.namePlaceholder}
                        />
                        {errors.name && (
                            <p className="mt-1.5 text-sm text-red-600">{Array.isArray(errors.name) ? errors.name[0] : errors.name}</p>
                        )}
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-semibold text-slate-700 mb-2">
                        {t.dashboard.sections.skills.skill.proficiency}{" "}
                        <span className="text-slate-400 font-normal text-xs">
                            ({t.dashboard.sections.skills.skill.proficiencyHint})
                        </span>
                    </label>
                    <div className="relative">
                        <select
                            value={skillData.proficiency}
                            onChange={(e) =>
                                setSkillData({...skillData, proficiency: e.target.value})
                            }
                            className="w-full rounded-xl border-slate-300 bg-white shadow-sm transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200/50 text-sm px-4 py-3"
                        >
                            <option value="">{t.dashboard.sections.skills.skill.selectProficiency || "Select Proficiency"}</option>
                            <option value="Beginner">{t.dashboard.sections.skills.skill.beginner || "Beginner"}</option>
                            <option value="Intermediate">{t.dashboard.sections.skills.skill.intermediate || "Intermediate"}</option>
                            <option value="Advanced">{t.dashboard.sections.skills.skill.advanced || "Advanced"}</option>
                            <option value="Expert">{t.dashboard.sections.skills.skill.expert || "Expert"}</option>
                        </select>
                        {errors.proficiency && (
                            <p className="mt-1.5 text-sm text-red-600">{errors.proficiency[0]}</p>
                        )}
                    </div>
                </div>

                <div className="flex justify-end pt-2">
                    <button
                        type="button"
                        onClick={() => {
                            !edit ? handleStoreSkill() : handleUpdateSkill();
                        }}
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
    );
}
