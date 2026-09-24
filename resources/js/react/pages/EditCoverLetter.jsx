import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import AuthLayout from '../Layouts/AuthLayout';
import { useLanguage } from '../context/LanguageContext';
import { getCoverLetter, createCoverLetter, updateCoverLetter, downloadCoverLetterPDF, getAvailableTemplates } from '../services/CoverLetterService';
import {
    Save,
    ArrowLeft,
    Download,
    Loader2,
    FileText,
    Building2,
    Sparkles,
    Layout as LayoutIcon
} from 'lucide-react';
import { toast } from 'sonner';
import EnhanceTextareaButton from '../components/EnhanceTextareaButton';

export default function EditCoverLetter() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { t, language } = useLanguage();
    const isEdit = !!id;

    const [templates, setTemplates] = useState([]);
    const [isTemplatesLoading, setIsTemplatesLoading] = useState(false);

    const [formData, setFormData] = useState({
        title: '',
        recipient_name: '',
        recipient_company: '',
        recipient_address: '',
        recipient_email: '',
        city: '',
        country: '',
        date: '',
        subject: '',
        content: '',
        style: 'empty'
    });

    const [isLoading, setIsLoading] = useState(isEdit);
    const [isSaving, setIsSaving] = useState(false);
    const [isDownloading, setIsDownloading] = useState(false);

    const strings = t.coverLetter || {};

    useEffect(() => {
        if (id) {
            fetchCoverLetter();
        }
        fetchTemplates();
    }, [id, language]);

    const fetchTemplates = async () => {
        setIsTemplatesLoading(true);
        try {
            const response = await getAvailableTemplates({ language });
            if (response.data.status) {
                setTemplates(response.data.data);
            }
        } catch (error) {
        } finally {
            setIsTemplatesLoading(false);
        }
    };

    const fetchCoverLetter = async () => {
        setIsLoading(true);
        try {
            const response = await getCoverLetter(id);
            if (response.data.status) {
                setFormData(response.data.data);
            }
        } catch (error) {
            toast.error(strings.messages?.loadError || 'Failed to load cover letter');
            navigate('/cover-letters');
        } finally {
            setIsLoading(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSave = async () => {
        if (!formData.title || !formData.content) {
            toast.error('Title and Content are required');
            return;
        }

        setIsSaving(true);
        try {
            let response;
            if (isEdit) {
                response = await updateCoverLetter(id, formData);
            } else {
                response = await createCoverLetter(formData);
            }

            if (response.data.status) {
                toast.success(strings.messages?.saveSuccess || 'Saved successfully');
                if (!isEdit) {
                    navigate(`/cover-letter/edit/${response.data.data.id}`);
                }
            }
        } catch (error) {
            toast.error(strings.messages?.saveError || 'Failed to save');
        } finally {
            setIsSaving(false);
        }
    };

    const handleDownload = async () => {
        if (!isEdit) {
            toast.error('Please save the cover letter first');
            return;
        }

        setIsDownloading(true);
        try {
            const response = await downloadCoverLetterPDF(id);
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `${formData.title.replace(/\s+/g, '_')}.pdf`);
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (error) {
            toast.error('Failed to download PDF');
        } finally {
            setIsDownloading(false);
        }
    };

    const applyTemplate = (type) => {
        const template = templates.find(t => t.job_type === type);
        if (template) {
            setFormData(prev => ({
                ...prev,
                subject: template.subject,
                content: template.content,
                style: type
            }));
            toast.success(strings.templates?.templateApplied || 'Template applied successfully');
        }
    };

    if (isLoading) {
        return (
            <AuthLayout>
                <div className="flex items-center justify-center min-h-screen">
                    <Loader2 className="h-10 w-10 animate-spin text-blue-600" />
                </div>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout>
            <div className="min-h-screen bg-slate-50">
                {/* Sticky Header */}
                <div className="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-slate-200">
                    <div className="max-w-[1600px] mx-auto px-4 h-20 flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <button
                                onClick={() => navigate('/cover-letters')}
                                className="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-600"
                            >
                                <ArrowLeft className="h-6 w-6" />
                            </button>
                            <div>
                                <h1 className="text-xl font-bold text-slate-900 truncate max-w-[200px] sm:max-w-md">
                                    {formData.title || (isEdit ? strings.title : strings.createNew)}
                                </h1>
                                <p className="text-xs text-slate-500 font-medium uppercase tracking-wider">
                                    {isEdit ? 'Editing' : 'Creating New'}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <button
                                onClick={handleDownload}
                                disabled={!isEdit || isDownloading}
                                className="hidden sm:inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl font-semibold hover:bg-slate-50 transition-all text-sm disabled:opacity-50"
                            >
                                {isDownloading ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Download className="h-4 w-4 mr-2" />}
                                {t.common.download}
                            </button>
                            <button
                                onClick={handleSave}
                                disabled={isSaving}
                                className="inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/25 text-sm disabled:opacity-50"
                            >
                                {isSaving ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Save className="h-4 w-4 mr-2" />}
                                {t.common.save || 'Save'}
                            </button>
                        </div>
                    </div>
                </div>

                <div className="max-w-[1600px] mx-auto px-4 py-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Editor Pane */}
                        <div className="space-y-6 animate-slide-in">
                            <div className="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                                <div className="p-6 border-b border-slate-100 bg-slate-50/50">
                                    <div className="flex items-center gap-2">
                                        <div className="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <FileText className="h-4 w-4 text-blue-600" />
                                        </div>
                                        <h2 className="font-bold text-slate-900">{strings.form?.basicInfo || "Basic Information"}</h2>
                                    </div>
                                </div>
                                <div className="p-6 space-y-4">
                                    <div>
                                        <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.internalTitle}</label>
                                        <input
                                            name="title"
                                            value={formData.title}
                                            onChange={handleChange}
                                            className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all"
                                            placeholder="e.g. Software Engineer at Google"
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.style || "Template"}</label>
                                            <select
                                                name="style"
                                                value={formData.style}
                                                onChange={(e) => {
                                                    const val = e.target.value;
                                                    handleChange(e);
                                                    if (val !== 'empty') {
                                                        applyTemplate(val);
                                                    }
                                                }}
                                                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all bg-white"
                                            >
                                                <option value="empty">{language === 'fr' ? 'Vide' : 'Empty'}</option>
                                                {templates.map(tpl => (
                                                    <option key={tpl.id} value={tpl.job_type}>
                                                        {tpl.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.date}</label>
                                            <input
                                                type="date"
                                                name="date"
                                                value={formData.date}
                                                onChange={handleChange}
                                                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                                <div className="p-6 border-b border-slate-100 bg-slate-50/50">
                                    <div className="flex items-center gap-2">
                                        <div className="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                            <Building2 className="h-4 w-4 text-purple-600" />
                                        </div>
                                        <h2 className="font-bold text-slate-900">{strings.form?.recipientDetails || "Recipient Details"}</h2>
                                    </div>
                                </div>
                                <div className="p-6 space-y-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.recipientName}</label>
                                            <input
                                                name="recipient_name"
                                                value={formData.recipient_name}
                                                onChange={handleChange}
                                                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.recipientCompany}</label>
                                            <input
                                                name="recipient_company"
                                                value={formData.recipient_company}
                                                onChange={handleChange}
                                                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.recipientAddress}</label>
                                        <input
                                            name="recipient_address"
                                            value={formData.recipient_address}
                                            onChange={handleChange}
                                            className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all"
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.city}</label>
                                            <input
                                                name="city"
                                                value={formData.city}
                                                onChange={handleChange}
                                                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.country}</label>
                                            <input
                                                name="country"
                                                value={formData.country}
                                                onChange={handleChange}
                                                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                                <div className="p-6 border-b border-slate-100 bg-slate-50/50">
                                    <div className="flex items-center gap-2">
                                        <div className="w-8 h-8 rounded-lg bg-pink-100 flex items-center justify-center">
                                            <Sparkles className="h-4 w-4 text-pink-600" />
                                        </div>
                                        <h2 className="font-bold text-slate-900">{strings.form?.contentTitle || "Content"}</h2>
                                    </div>
                                </div>
                                <div className="p-6 space-y-4">
                                    <div>
                                        <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.subject}</label>
                                        <input
                                            name="subject"
                                            value={formData.subject}
                                            onChange={handleChange}
                                            className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all font-semibold"
                                            placeholder="e.g. Application for Software Engineer Position"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-slate-700 mb-1.5">{strings.form?.content}</label>
                                        <textarea
                                            name="content"
                                            value={formData.content}
                                            onChange={handleChange}
                                            rows={15}
                                            className="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all resize-none font-serif text-lg leading-relaxed"
                                            placeholder="Write your cover letter here..."
                                        />
                                        <div className="mt-2 flex justify-end">
                                            <EnhanceTextareaButton
                                                value={formData.content}
                                                context="cover letter content"
                                                onEnhanced={(enhanced) =>
                                                    setFormData((prev) => ({ ...prev, content: enhanced }))
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Preview Pane */}
                        <div className="hidden lg:block sticky top-28 h-[calc(100vh-140px)]">
                            <div className="bg-slate-200 rounded-3xl h-full shadow-inner relative overflow-hidden flex flex-col items-center justify-center p-8 group">
                                <div className="bg-white w-full h-full max-w-[600px] shadow-2xl rounded-sm p-12 overflow-y-auto scrollbar-hide text-slate-800 font-serif">
                                    <div className="mb-8">
                                        <p className="font-bold text-xl">{formData.recipient_name || 'Recipient Name'}</p>
                                        <p>{formData.recipient_company || 'Company Name'}</p>
                                        <p>{formData.recipient_address || 'Address Line'}</p>
                                        <p>{formData.city}{formData.city && formData.country ? ', ' : ''}{formData.country}</p>
                                    </div>

                                    <div className="mb-8 text-right">
                                        <p>{formData.date || new Date().toLocaleDateString()}</p>
                                    </div>

                                    <div className="mb-8">
                                        <p className="font-bold">{formData.subject || 'Subject Line'}</p>
                                    </div>

                                    <div className="whitespace-pre-wrap leading-relaxed text-slate-700">
                                        {formData.content || 'Your content will appear here...'}
                                    </div>

                                    <div className="mt-12">
                                        <p>Sincerely,</p>
                                        <div className="h-12"></div>
                                        <p className="font-bold">Your Name</p>
                                    </div>
                                </div>

                                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm pointer-events-none">
                                    <div className="bg-white p-4 rounded-2xl shadow-xl flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <LayoutIcon className="h-5 w-5 text-blue-600" />
                                        </div>
                                        <p className="font-bold text-slate-900">Live Preview Mode</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}
