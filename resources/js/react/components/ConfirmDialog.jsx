import { useEffect } from "react";
import { createPortal } from "react-dom";

export default function ConfirmDialog({ 
    isOpen, 
    onClose, 
    onConfirm, 
    title = "Confirm Delete", 
    message = "Are you sure you want to delete this item?",
    itemName = null,
    confirmText = "Yes",
    cancelText = "No"
}) {
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'unset';
        }
        return () => {
            document.body.style.overflow = 'unset';
        };
    }, [isOpen]);

    if (!isOpen) return null;

    return createPortal(
        <div className="fixed inset-0 z-[9999] overflow-y-auto">
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-black/20 backdrop-blur-sm transition-opacity"
                onClick={onClose}
            ></div>

            {/* Dialog */}
            <div className="flex min-h-full items-center justify-center p-4">
                <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    {/* Title */}
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                        {title}
                    </h3>

                    {/* Message */}
                    <p className="text-sm text-gray-600 mb-6">
                        {message}
                        {itemName && (
                            <span className="block mt-2 font-semibold text-gray-900">
                                "{itemName}"
                            </span>
                        )}
                    </p>

                    {/* Buttons */}
                    <div className="flex justify-end gap-3">
                        <button
                            onClick={onClose}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                        >
                            {cancelText}
                        </button>
                        <button
                            onClick={async () => {
                                await onConfirm();
                                onClose();
                            }}
                            className="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                        >
                            {confirmText}
                        </button>
                    </div>
                </div>
            </div>
        </div>,
        document.body
    );
}

