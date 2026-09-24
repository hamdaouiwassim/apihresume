import { useState } from "react";
import NewSkill from "./NewSkill";
import { deleteSkill } from "../services/SkillService";
import { toast } from "sonner";
import { useLanguage } from "../context/LanguageContext";
import ConfirmDialog from "./ConfirmDialog";
import { Edit2, Trash2, Loader2 } from "lucide-react";

export default function ShowSkill({ skill, index, hide, resumeId, onSave, onDelete, onPreviewChange, onPreviewClear }) {
    const { t } = useLanguage();
    const [edit, setEdit] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const buttonBase = "inline-flex items-center gap-2 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2";
    const buttonVariants = {
      edit: `${buttonBase} px-3 py-2 border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 focus:ring-blue-400 focus:ring-offset-white`,
      delete: `${buttonBase} px-3 py-2 border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-400 focus:ring-offset-white`,
    };

    const deleteSkillHandler = async () => {
        setIsDeleting(true);
        try {
            const response = await deleteSkill(skill.id);
            toast.success(response.data.message || "Skill deleted successfully");
            if (onDelete) onDelete();
        } catch (error) {
            toast.error("Error deleting skill. Please try again.");
            setIsDeleting(false);
        }
    };

    const handleHide = () => {
        setEdit(false);
        if (hide) hide();
    };

    return (
        <>
            {edit ? (
                <NewSkill 
                    skill={skill} 
                    index={index} 
                    edit={true} 
                    hide={handleHide} 
                    resumeId={resumeId}
                    onSave={onSave}
                    onPreviewChange={onPreviewChange}
                    onPreviewClear={onPreviewClear}
                />
            ) : (
                <>
                    <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex-1 min-w-0">
                                <h5 className="text-base font-semibold text-slate-900 mb-1">
                                    {skill.name}
                                </h5>
                                {skill.proficiency && (
                                    <p className="text-sm text-slate-600">
                                        {skill.proficiency}
                                    </p>
                                )}
                            </div>
                            <div className="flex gap-2 flex-shrink-0">
                                <button
                                    className={buttonVariants.edit}
                                    onClick={() => setEdit(true)}
                                    disabled={isDeleting}
                                    title="Edit skill"
                                >
                                    {isDeleting ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <Edit2 className="h-4 w-4" />
                                    )}
                                </button>
                                <button
                                    className={buttonVariants.delete}
                                    onClick={() => setShowDeleteDialog(true)}
                                    disabled={isDeleting}
                                    title="Delete skill"
                                >
                                    {isDeleting ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <Trash2 className="h-4 w-4" />
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>
                    <ConfirmDialog
                        isOpen={showDeleteDialog}
                        onClose={() => setShowDeleteDialog(false)}
                        onConfirm={deleteSkillHandler}
                        title="Delete Skill"
                        message="Are you sure you want to delete this skill? This action cannot be undone."
                        itemName={skill.name}
                        confirmText="Yes"
                        cancelText="No"
                    />
                </>
            )}
        </>
    );
}
