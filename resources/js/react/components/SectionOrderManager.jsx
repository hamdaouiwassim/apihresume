import { useState, useEffect } from "react";
import { GripVertical, Save, Loader2, X, Check } from "lucide-react";
import { update } from "../services/resumeService";
import { toast } from "sonner";

const SectionOrderManager = ({ 
  sections, 
  sectionOrder, 
  onOrderChange, 
  resumeId,
  sectionLabels,
  isReorderMode,
  onToggleReorderMode
}) => {
  const [localOrder, setLocalOrder] = useState(sectionOrder || sections);
  const [isSaving, setIsSaving] = useState(false);

  // Sync local order when sectionOrder prop changes
  useEffect(() => {
    if (sectionOrder && sectionOrder.length > 0) {
      setLocalOrder(sectionOrder);
    }
  }, [sectionOrder]);

  const handleSave = async () => {
    setIsSaving(true);
    try {
      await update(resumeId, { section_order: localOrder });
      toast.success("Section order saved successfully");
      onToggleReorderMode(false);
    } catch (error) {
      toast.error("Failed to save section order");
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancel = () => {
    // Reset to original order
    const originalOrder = sectionOrder || sections;
    setLocalOrder(originalOrder);
    onOrderChange(originalOrder); // Reset preview too
    onToggleReorderMode(false);
  };

  const handleToggle = () => {
    if (!isReorderMode) {
      // Entering reorder mode - use current order
      setLocalOrder(sectionOrder || sections);
    }
    onToggleReorderMode(!isReorderMode);
  };

  return (
    <div className="flex items-center gap-2">
      <button
        type="button"
        onClick={handleToggle}
        className={`inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors ${
          isReorderMode
            ? "bg-blue-600 text-white hover:bg-blue-700"
            : "text-gray-700 bg-white border border-gray-300 hover:bg-gray-50"
        }`}
      >
        <GripVertical className="h-4 w-4" />
        {isReorderMode ? "Done Reordering" : "Reorder Sections"}
      </button>
      
      {isReorderMode && (
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={handleCancel}
            className="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            <X className="h-4 w-4" />
          </button>
          <button
            type="button"
            onClick={handleSave}
            disabled={isSaving}
            className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSaving ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <Check className="h-4 w-4" />
            )}
          </button>
        </div>
      )}
    </div>
  );
};

export default SectionOrderManager;

