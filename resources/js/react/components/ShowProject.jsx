import { useState } from "react";
import NewProject from "./NewProject";
import { deleteProject } from "../services/ProjectService";
import { toast } from "sonner";
import ConfirmDialog from "./ConfirmDialog";
import { Edit2, Trash2, Loader2, ExternalLink } from "lucide-react";

export default function ShowProject({ project, index, hide, resumeId, experiences = [], onSave, onDelete, onPreviewChange, onPreviewClear }) {
    const [edit, setEdit] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const buttonBase = "inline-flex items-center gap-2 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2";
    const buttonVariants = {
      edit: `${buttonBase} px-3 py-2 border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 focus:ring-blue-400 focus:ring-offset-white`,
      delete: `${buttonBase} px-3 py-2 border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-400 focus:ring-offset-white`,
    };

    const deleteProjectHandler = async () => {
        setIsDeleting(true);
        try {
            const response = await deleteProject(project.id);
            toast.success(response.data.message || "Project deleted successfully");
            if (onDelete) onDelete();
        } catch (error) {
            toast.error("Error deleting project. Please try again.");
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
                <NewProject
                    project={project}
                    index={index}
                    edit={true}
                    hide={handleHide}
                    resumeId={resumeId}
                    experiences={experiences}
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
                                    {project.name}
                                </h5>
                                {project.experience_id && experiences?.length > 0 && (() => {
                                    const exp = experiences.find((e) => e.id === project.experience_id || e.id === Number(project.experience_id));
                                    return exp ? (
                                        <p className="text-xs text-slate-500 mb-1">
                                            Under: {exp.company}{exp.position ? ` – ${exp.position}` : ""}
                                        </p>
                                    ) : null;
                                })()}
                                {project.technologies && (
                                    <p className="text-sm text-slate-500 mb-2">
                                        {project.technologies}
                                    </p>
                                )}
                                {project.startDate && project.endDate && (
                                    <p className="text-xs text-slate-500 mb-2">
                                        {new Date(project.startDate).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })} - {new Date(project.endDate).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}
                                    </p>
                                )}
                                {project.description && (
                                    <p className="text-sm text-slate-600 mt-2 line-clamp-2">
                                        {project.description}
                                    </p>
                                )}
                                {project.url && (
                                    <a 
                                        href={project.url} 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 mt-2"
                                    >
                                        <ExternalLink className="h-3 w-3" />
                                        View Project
                                    </a>
                                )}
                            </div>
                            <div className="flex gap-2 flex-shrink-0">
                                <button
                                    className={buttonVariants.edit}
                                    onClick={() => setEdit(true)}
                                    disabled={isDeleting}
                                    title="Edit project"
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
                                    title="Delete project"
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
                        onConfirm={deleteProjectHandler}
                        title="Delete Project"
                        message="Are you sure you want to delete this project? This action cannot be undone."
                        itemName={project.name}
                        confirmText="Yes"
                        cancelText="No"
                    />
                </>
            )}
        </>
    );
}

