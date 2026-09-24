import { useState, useEffect, useMemo } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import AdminLayout from '../../Layouts/AdminLayout';
import { getAdminResumes, getAdminResume } from '../../services/adminService';
import { toast } from 'sonner';
import { 
    Loader2, 
    Search, 
    FileText, 
    User, 
    Calendar,
    Eye,
    Download,
    Filter,
    X,
    Maximize2
} from 'lucide-react';
import { useLanguage } from '../../context/LanguageContext';
import { buildResumeTemplateData } from '../../utils/resumeTemplateMapper';
import ResumeTemplatePreview from '../../components/ResumeTemplatePreview';
import { deriveTemplateLayout } from '../../utils/templateStyles';
import AdminListPagination from '../../components/admin/AdminListPagination';
import { DEFAULT_ADMIN_PER_PAGE } from '../../constants/adminPagination';

export default function GeneratedCV() {
    const navigate = useNavigate();
    const { language, t } = useLanguage();
    const [resumes, setResumes] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedResume, setSelectedResume] = useState(null);
    const [isViewingResume, setIsViewingResume] = useState(false);
    const [previewResume, setPreviewResume] = useState(null);
    const [isPreviewOpen, setIsPreviewOpen] = useState(false);
    const [perPage, setPerPage] = useState(DEFAULT_ADMIN_PER_PAGE);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        total: 0,
    });
    const locale = language === "fr" ? "fr-FR" : "en-US";

    useEffect(() => {
        fetchResumes(1);
    }, [searchQuery, perPage]);

    const fetchResumes = async (page = 1) => {
        try {
            setIsLoading(true);
            const params = {
                per_page: perPage,
                page: page,
                ...(searchQuery && { search: searchQuery })
            };
            const response = await getAdminResumes(params);
            if (response.data.status) {
                const data = response.data.data;
                const resumesList = data.data || data || [];
                setResumes(resumesList);
                
                if (data.current_page !== undefined) {
                    setPagination({
                        current_page: data.current_page || 1,
                        last_page: data.last_page || 1,
                        total: data.total || resumesList.length,
                    });
                } else {
                    setPagination({
                        current_page: 1,
                        last_page: 1,
                        total: resumesList.length,
                    });
                }
            } else {
                toast.error('Failed to load resumes');
            }
        } catch (error) {
            toast.error('Failed to load resumes');
        } finally {
            setIsLoading(false);
        }
    };

    const handleViewResume = async (resumeId) => {
        try {
            const response = await getAdminResume(resumeId);
            if (response.data.status) {
                setSelectedResume(response.data.data);
                setIsViewingResume(true);
            } else {
                toast.error('Failed to load resume details');
            }
        } catch (error) {
            toast.error('Failed to load resume details');
        }
    };

    const handlePreviewCV = async (resumeId) => {
        try {
            const response = await getAdminResume(resumeId);
            if (response.data.status) {
                setPreviewResume(response.data.data);
                setIsPreviewOpen(true);
            } else {
                toast.error('Failed to load resume for preview');
            }
        } catch (error) {
            toast.error('Failed to load resume for preview');
        }
    };

    const formatResumeDataForPreview = (resume) => {
        if (!resume) return {
            full_name: "",
            email: "",
            phone: "",
            location: "",
            job_title: "",
            professional_summary: "",
            avatar: "",
            experiences: [],
            educations: [],
            skills: [],
            hobbies: [],
            certificates: [],
            languages: [],
            template_layout: "classic",
            template_id: null,
        };
        
        const basicInfo = resume.basicInfo || resume.basic_info || {};
        const templateLayout = resume.template_layout || deriveTemplateLayout(resume.template) || "classic";
        const templateId = resume.template_id || resume.template?.id || null;

        return {
            full_name: basicInfo.full_name || resume.name || "",
            email: basicInfo.email || "",
            phone: basicInfo.phone || "",
            location: basicInfo.location || "",
            job_title: basicInfo.job_title || "",
            professional_summary: basicInfo.professional_summary || basicInfo.summary || "",
            avatar: basicInfo.avatar || "",
            linkedin: basicInfo.linkedin || "",
            website: basicInfo.website || basicInfo.github || "",
            experiences: resume.experiences || [],
            educations: resume.educations || [],
            skills: resume.skills || [],
            hobbies: resume.hobbies || resume.interests || [],
            certificates: resume.certificates || [],
            languages: resume.languages || [],
            template_layout: templateLayout,
            template_id: templateId,
        };
    };

    const previewData = useMemo(() => {
        if (!previewResume) return null;
        const formData = formatResumeDataForPreview(previewResume);
        return buildResumeTemplateData(formData, locale, {
            present: t?.preview?.present,
        });
    }, [previewResume, locale, t?.preview?.present]);

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'N/A';
        }
    };

    if (isViewingResume && selectedResume) {
        return (
            <AdminLayout>
                <div className="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="mb-8">
                            <button
                                onClick={() => {
                                    setIsViewingResume(false);
                                    setSelectedResume(null);
                                }}
                                className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4 transition-colors"
                            >
                                <X className="h-4 w-4 mr-2" />
                                Back to List
                            </button>
                            <h1 className="text-4xl font-bold text-gray-900 mb-2">Resume Details</h1>
                        </div>

                        <div className="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
                            <div className="mb-6">
                                <h2 className="text-2xl font-bold text-gray-900 mb-2">{selectedResume.name}</h2>
                                <div className="flex items-center gap-4 text-sm text-gray-600">
                                    {selectedResume.user && (
                                        <div className="flex items-center">
                                            <User className="h-4 w-4 mr-2" />
                                            {selectedResume.user.name} ({selectedResume.user.email})
                                        </div>
                                    )}
                                    {selectedResume.template && (
                                        <div className="flex items-center">
                                            <FileText className="h-4 w-4 mr-2" />
                                            Template: {selectedResume.template.name}
                                        </div>
                                    )}
                                    <div className="flex items-center">
                                        <Calendar className="h-4 w-4 mr-2" />
                                        {formatDate(selectedResume.updated_at)}
                                    </div>
                                </div>
                            </div>

                            {/* Resume Content Preview */}
                            <div className="space-y-6">
                                {selectedResume.basicInfo && (
                                    <div>
                                        <h3 className="text-lg font-bold text-gray-900 mb-2">Basic Information</h3>
                                        <div className="bg-gray-50 p-4 rounded-lg">
                                            <p className="text-gray-700">{JSON.stringify(selectedResume.basicInfo, null, 2)}</p>
                                        </div>
                                    </div>
                                )}

                                {selectedResume.experiences && selectedResume.experiences.length > 0 && (
                                    <div>
                                        <h3 className="text-lg font-bold text-gray-900 mb-2">Experiences ({selectedResume.experiences.length})</h3>
                                        <div className="space-y-2">
                                            {selectedResume.experiences.map((exp, idx) => (
                                                <div key={idx} className="bg-gray-50 p-4 rounded-lg">
                                                    <p className="font-semibold text-gray-900">{exp.position || exp.title || 'N/A'}</p>
                                                    <p className="text-sm text-gray-600">{exp.company || 'N/A'}</p>
                                                    {(exp.startDate || exp.start_date || exp.endDate || exp.end_date) && (
                                                        <p className="text-xs text-gray-500 mt-1">
                                                            {exp.startDate || exp.start_date 
                                                                ? new Date(exp.startDate || exp.start_date).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
                                                                : ''}
                                                            {exp.endDate || exp.end_date 
                                                                ? ` - ${new Date(exp.endDate || exp.end_date).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}`
                                                                : (exp.startDate || exp.start_date) ? ' - Present' : ''}
                                                        </p>
                                                    )}
                                                    {exp.description && (
                                                        <p className="text-sm text-gray-600 mt-2 line-clamp-3">{exp.description}</p>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {selectedResume.educations && selectedResume.educations.length > 0 && (
                                    <div>
                                        <h3 className="text-lg font-bold text-gray-900 mb-2">Education ({selectedResume.educations.length})</h3>
                                        <div className="space-y-2">
                                            {selectedResume.educations.map((edu, idx) => (
                                                <div key={idx} className="bg-gray-50 p-4 rounded-lg">
                                                    <p className="font-semibold text-gray-900">{edu.degree || 'N/A'}</p>
                                                    <p className="text-sm text-gray-600">{edu.institution || 'N/A'}</p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                <div className="flex gap-4 pt-4">
                                    <Link
                                        to={`/resume/edit/${selectedResume.id}`}
                                        className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                                    >
                                        Edit Resume
                                    </Link>
                                    <button
                                        onClick={() => navigate(`/admin/users/${selectedResume.user_id}`)}
                                        className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                                    >
                                        View User
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout>
            <div className="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-4xl font-bold text-gray-900 mb-2">Generated CVs</h1>
                        <p className="text-gray-600">View and manage all generated resumes</p>
                    </div>

                    {/* Search and Filters */}
                    <div className="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 mb-6">
                        <div className="flex flex-col md:flex-row gap-4">
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <input
                                    type="text"
                                    placeholder="Search by resume name, user name, or email..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Resumes Table */}
                    {isLoading ? (
                        <div className="bg-white rounded-2xl shadow-lg p-12 border border-gray-100">
                            <div className="flex items-center justify-center">
                                <Loader2 className="h-8 w-8 animate-spin text-purple-600" />
                                <span className="ml-3 text-gray-600">Loading resumes...</span>
                            </div>
                        </div>
                    ) : (
                        <div className="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-200">
                                        <tr>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Resume</th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Template</th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Last Updated</th>
                                            <th className="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {resumes.length > 0 ? (
                                            resumes.map((resume) => (
                                                <tr key={resume.id} className="hover:bg-gray-50 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <FileText className="h-5 w-5 text-purple-600 mr-3" />
                                                            <div>
                                                                <div className="text-sm font-semibold text-gray-900">{resume.name}</div>
                                                                <div className="text-xs text-gray-500">ID: #{resume.id}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {resume.user ? (
                                                            <Link
                                                                to={`/admin/users/${resume.user.id}`}
                                                                className="flex items-center hover:text-purple-600 transition-colors"
                                                            >
                                                                <img
                                                                    src={resume.user.avatar || "https://api.dicebear.com/7.x/avataaars/svg?seed=" + resume.user.email}
                                                                    alt={resume.user.name}
                                                                    className="h-8 w-8 rounded-full mr-2"
                                                                />
                                                                <div>
                                                                    <div className="text-sm font-semibold text-gray-900">{resume.user.name}</div>
                                                                    <div className="text-xs text-gray-500">{resume.user.email}</div>
                                                                </div>
                                                            </Link>
                                                        ) : (
                                                            <span className="text-sm text-gray-400">N/A</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {resume.template ? (
                                                            <span className="text-sm text-gray-900">{resume.template.name}</span>
                                                        ) : (
                                                            <span className="text-sm text-gray-400">N/A</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center text-sm text-gray-900">
                                                            <Calendar className="h-4 w-4 mr-2 text-gray-400" />
                                                            {formatDate(resume.updated_at)}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end space-x-2">
                                                            <button
                                                                onClick={() => handlePreviewCV(resume.id)}
                                                                className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                                title="Preview CV"
                                                            >
                                                                <Maximize2 className="h-4 w-4" />
                                                            </button>
                                                            <button
                                                                onClick={() => handleViewResume(resume.id)}
                                                                className="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                                                title="View Details"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </button>
                                                            <Link
                                                                to={`/resume/edit/${resume.id}`}
                                                                className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                                                title="Edit Resume"
                                                            >
                                                                <FileText className="h-4 w-4" />
                                                            </Link>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="5" className="px-6 py-12 text-center">
                                                    <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                                    <p className="text-gray-500">No resumes found</p>
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {!isLoading && pagination.total > 0 && (
                                <AdminListPagination
                                    currentPage={pagination.current_page}
                                    lastPage={pagination.last_page}
                                    perPage={perPage}
                                    total={pagination.total}
                                    onPageChange={(p) => fetchResumes(p)}
                                    onPerPageChange={setPerPage}
                                    itemLabel="resumes"
                                />
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* CV Preview Modal */}
            {isPreviewOpen && previewData && (
                <div 
                    className="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-75"
                    onClick={() => setIsPreviewOpen(false)}
                >
                    <div className="flex min-h-full items-center justify-center p-4">
                        <div 
                            className="relative bg-white rounded-lg shadow-xl w-full max-w-4xl my-8"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {/* Modal Header */}
                            <div className="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200">
                                <h2 className="text-lg sm:text-2xl font-semibold text-gray-900">
                                    CV Preview - {previewResume?.name || 'Resume'}
                                </h2>
                                <button
                                    onClick={() => setIsPreviewOpen(false)}
                                    className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                    title="Close"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>

                            {/* Preview Content */}
                            <div className="p-4 sm:p-8 bg-gray-50">
                                <div className="bg-white rounded-lg shadow-lg p-4 sm:p-8">
                                    <ResumeTemplatePreview
                                        resume={previewData}
                                        templateKey={previewData.template_layout || 'classic'}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

