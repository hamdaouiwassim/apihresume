import { useMemo, lazy, Suspense } from 'react';
import { Link } from 'react-router-dom';
import { X, Edit } from 'lucide-react';
import { useLanguage } from '../../context/LanguageContext';
import { buildAdminResumePreviewData } from '../../utils/adminResumePreview';
import ResumePreviewSkeleton from '../ResumePreviewSkeleton';

const ResumeTemplatePreview = lazy(() => import('../ResumeTemplatePreview'));

export default function AdminResumePreviewModal({
    resume,
    onClose,
    formatDate,
}) {
    const { language, t } = useLanguage();
    const locale = language === 'fr' ? 'fr-FR' : 'en-US';

    const previewData = useMemo(() => {
        if (!resume || resume._loading) return null;
        return buildAdminResumePreviewData(resume, locale, {
            present: t?.preview?.present,
        });
    }, [resume, locale, t?.preview?.present]);

    if (!resume) return null;

    return (
        <div
            className="fixed inset-0 z-50 overflow-y-auto bg-gray-900/75"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
            aria-label="CV preview"
        >
            <div className="flex min-h-full items-center justify-center p-4">
                <div
                    className="relative w-full max-w-4xl my-8 rounded-2xl bg-white shadow-xl"
                    onClick={(e) => e.stopPropagation()}
                >
                    <div className="flex items-center justify-between border-b border-gray-200 px-4 sm:px-6 py-4">
                        <div>
                            <h2 className="text-lg sm:text-xl font-bold text-gray-900">
                                CV Preview{resume.name ? ` — ${resume.name}` : ''}
                            </h2>
                            {!resume._loading && (
                                <p className="text-sm text-gray-500">
                                    #{resume.id}
                                    {resume.template?.name && ` · ${resume.template.name}`}
                                    {resume.updated_at && formatDate && (
                                        <> · Updated {formatDate(resume.updated_at)}</>
                                    )}
                                </p>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    <div className="p-4 sm:p-6 bg-gray-50 max-h-[75vh] overflow-y-auto">
                        {resume._loading || !previewData ? (
                            <div className="bg-white rounded-lg shadow-inner p-6">
                                <ResumePreviewSkeleton />
                            </div>
                        ) : (
                            <div className="bg-white rounded-lg shadow-lg p-4 sm:p-8">
                                <Suspense fallback={<ResumePreviewSkeleton />}>
                                    <ResumeTemplatePreview
                                        resume={previewData}
                                        templateKey={previewData.template_layout || 'classic'}
                                    />
                                </Suspense>
                            </div>
                        )}
                    </div>

                    {!resume._loading && resume.id && (
                        <div className="flex flex-wrap gap-2 border-t border-gray-200 px-4 sm:px-6 py-4">
                            <Link
                                to={`/resume/edit/${resume.id}`}
                                className="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700"
                            >
                                <Edit className="h-4 w-4" />
                                Open editor
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
