import { useState } from "react";
import NewLanguage from "./NewLanguage";
import { deleteLanguage } from "../services/LanguageService";
import { toast } from "sonner";
import ConfirmDialog from "./ConfirmDialog";
import { Edit2, Trash2, Loader2 } from "lucide-react";

export default function ShowLanguage({ lang, index, hide, resumeId, onSave, onDelete, onPreviewChange, onPreviewClear }) {
    const [edit, setEdit] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const buttonBase = "inline-flex items-center gap-2 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2";
    const buttonVariants = {
      edit: `${buttonBase} px-3 py-2 border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 focus:ring-blue-400 focus:ring-offset-white`,
      delete: `${buttonBase} px-3 py-2 border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-400 focus:ring-offset-white`,
    };

    const deleteLanguageHandler = async () => {
        setIsDeleting(true);
        try {
            const response = await deleteLanguage(lang.id);
            toast.success(response.data.message || "Language deleted successfully");
            if (onDelete) onDelete();
        } catch (error) {
            toast.error("Error deleting language. Please try again.");
            setIsDeleting(false);
        }
    };

    const handleHide = () => {
        setEdit(false);
        if (hide) hide();
    };

    const getProficiencyColor = (proficiency) => {
        switch (proficiency) {
            case 'Native':
                return 'bg-emerald-100 text-emerald-700';
            case 'Fluent':
                return 'bg-blue-100 text-blue-700';
            case 'Intermediate':
                return 'bg-amber-100 text-amber-700';
            case 'Basic':
                return 'bg-gray-100 text-gray-700';
            default:
                return 'bg-gray-100 text-gray-700';
        }
    };

    return (
        <>
            {edit ? (
                <NewLanguage
                    lang={lang}
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
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex-1 min-w-0">
                                <h5 className="text-base font-semibold text-slate-900 mb-1">
                                    {lang.language}
                                </h5>
                                <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${getProficiencyColor(lang.proficiency)}`}>
                                    {lang.proficiency}
                                </span>
                            </div>
                            <div className="flex gap-2 flex-shrink-0">
                                <button
                                    className={buttonVariants.edit}
                                    onClick={() => setEdit(true)}
                                    disabled={isDeleting}
                                    title="Edit language"
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
                                    title="Delete language"
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
                        onConfirm={deleteLanguageHandler}
                        title="Delete Language"
                        message="Are you sure you want to delete this language? This action cannot be undone."
                        itemName={`${lang.language} - ${lang.proficiency}`}
                        confirmText="Yes"
                        cancelText="No"
                    />
                </>
            )}
        </>
    );
}

