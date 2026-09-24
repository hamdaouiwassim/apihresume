import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  getAdminBlogPost,
  createBlogPost,
  updateBlogPost,
} from '../../services/blogService';
import { toast } from 'sonner';
import { useLanguage } from '../../context/LanguageContext';
import EnhanceTextareaButton from '../../components/EnhanceTextareaButton';
import BlogRichTextEditor from '../../components/BlogRichTextEditor';
import { isBlogContentEmpty, htmlToPlainText, plainTextToBlogHtml } from '../../utils/blogContent';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';

const EMPTY_FORM = {
  title: '',
  excerpt: '',
  content: '',
  featured_image: '',
  status: 'draft',
  published_at: '',
};

function toDatetimeLocalValue(dateString) {
  if (!dateString) return '';
  const d = new Date(dateString);
  if (Number.isNaN(d.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function postToFormData(post) {
  return {
    title: post.title || '',
    excerpt: post.excerpt || '',
    content: post.content || '',
    featured_image: post.featured_image || '',
    status: post.status || 'draft',
    published_at: toDatetimeLocalValue(post.published_at),
  };
}

export default function AdminBlogPostEditor() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { t } = useLanguage();
  const blog = t?.admin?.blog || {};
  const fileInputRef = useRef(null);

  const [isLoading, setIsLoading] = useState(isEdit);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState(EMPTY_FORM);
  const [featuredImageFile, setFeaturedImageFile] = useState(null);
  const [featuredImagePreview, setFeaturedImagePreview] = useState(null);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (!isEdit) return;

    let cancelled = false;
    (async () => {
      try {
        setIsLoading(true);
        const response = await getAdminBlogPost(id);
        if (cancelled) return;
        if (response.data.status) {
          const post = response.data.data;
          setFormData(postToFormData(post));
          setFeaturedImagePreview(post.featured_image || null);
        } else {
          toast.error(blog.fetchError || 'Failed to load blog post');
          navigate('/admin/blog');
        }
      } catch {
        if (!cancelled) {
          toast.error(blog.fetchError || 'Failed to load blog post');
          navigate('/admin/blog');
        }
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [id, isEdit, navigate, blog.fetchError]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = () => {
    const newErrors = {};
    if (!formData.title.trim()) {
      newErrors.title = blog.validation?.titleRequired || 'Title is required';
    }
    if (isBlogContentEmpty(formData.content)) {
      newErrors.content = blog.validation?.contentRequired || 'Content is required';
    }
    return newErrors;
  };

  const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setFeaturedImageFile(file);
    const reader = new FileReader();
    reader.onloadend = () => setFeaturedImagePreview(reader.result);
    reader.readAsDataURL(file);
  };

  const clearFeaturedImage = () => {
    setFeaturedImageFile(null);
    setFeaturedImagePreview(null);
    setFormData((prev) => ({ ...prev, featured_image: '' }));
    if (fileInputRef.current) fileInputRef.current.value = '';
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const validationErrors = validateForm();
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }

    setIsSubmitting(true);
    try {
      const submitData = featuredImageFile
        ? (() => {
            const formDataObj = new FormData();
            Object.keys(formData).forEach((key) => {
              if (formData[key] !== '') {
                formDataObj.append(key, formData[key]);
              }
            });
            formDataObj.append('featured_image_file', featuredImageFile);
            return formDataObj;
          })()
        : formData;

      const response = isEdit
        ? await updateBlogPost(id, submitData)
        : await createBlogPost(submitData);

      if (response.data.status) {
        toast.success(
          isEdit
            ? blog.updateSuccess || 'Blog post updated successfully'
            : blog.createSuccess || 'Blog post created successfully'
        );
        navigate('/admin/blog');
      }
    } catch (error) {
      toast.error(error.response?.data?.message || blog.saveError || 'Failed to save blog post');
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const pageTitle = isEdit
    ? blog.editPost || 'Edit Post'
    : blog.newPost || 'New Post';

  return (
    <AdminLayout>
      <div className="max-w-4xl mx-auto animate-in fade-in duration-500">
        <div className="mb-6">
          <Link
            to="/admin/blog"
            className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4 transition-colors"
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            {blog.backToList || 'Back to blog posts'}
          </Link>
          <h1 className="text-3xl font-bold text-gray-900">{pageTitle}</h1>
          <p className="text-gray-600 mt-1">
            {isEdit
              ? blog.editSubtitle || 'Update your blog post and save changes.'
              : blog.createSubtitle || 'Write and publish a new article.'}
          </p>
        </div>

        {isLoading ? (
          <div className="flex items-center justify-center min-h-[400px] bg-white rounded-2xl shadow-lg border border-gray-100">
            <Loader2 className="h-10 w-10 animate-spin text-purple-600" />
          </div>
        ) : (
          <form
            onSubmit={handleSubmit}
            className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sm:p-8 space-y-6"
          >
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                {blog.titleLabel || 'Title'} <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                name="title"
                value={formData.title}
                onChange={handleInputChange}
                autoFocus={!isEdit}
                className={`w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 ${
                  errors.title ? 'border-red-500 bg-red-50' : 'border-gray-300'
                }`}
              />
              {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
            </div>

            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                {blog.excerptLabel || 'Excerpt'}
              </label>
              <textarea
                name="excerpt"
                value={formData.excerpt}
                onChange={handleInputChange}
                rows={3}
                className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 resize-y"
                placeholder={blog.excerptPlaceholder || 'Brief summary of the post...'}
              />
              <div className="mt-2 flex justify-end">
                <EnhanceTextareaButton
                  value={formData.excerpt}
                  context="blog excerpt"
                  onEnhanced={(enhanced) =>
                    setFormData((prev) => ({ ...prev, excerpt: enhanced }))
                  }
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                {blog.contentLabel || 'Content'} <span className="text-red-500">*</span>
              </label>
              <BlogRichTextEditor
                editorKey={isEdit ? id : 'new'}
                value={formData.content}
                onChange={(html) => {
                  setFormData((prev) => ({ ...prev, content: html }));
                  if (errors.content) {
                    setErrors((prev) => ({ ...prev, content: '' }));
                  }
                }}
                onImportMetadata={(fields) => {
                  setFormData((prev) => ({
                    ...prev,
                    ...(fields.title ? { title: fields.title } : {}),
                    ...(fields.excerpt ? { excerpt: fields.excerpt } : {}),
                  }));
                }}
                labels={{
                  importMarkdown: blog.importMarkdown,
                  importHint: blog.importMarkdownHint,
                  importSuccess: blog.importMarkdownSuccess,
                  importError: blog.importMarkdownError,
                  importReplaceConfirm: blog.importMarkdownReplaceConfirm,
                }}
                placeholder={blog.contentPlaceholder || 'Write your blog post…'}
                hasError={Boolean(errors.content)}
              />
              <div className="mt-2 flex justify-end">
                <EnhanceTextareaButton
                  value={htmlToPlainText(formData.content)}
                  context="blog post content"
                  onEnhanced={(enhanced) =>
                    setFormData((prev) => ({
                      ...prev,
                      content: plainTextToBlogHtml(enhanced),
                    }))
                  }
                />
              </div>
              {errors.content && <p className="mt-1 text-sm text-red-600">{errors.content}</p>}
            </div>

            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                {blog.featuredImageLabel || 'Featured Image'}
              </label>

              {(featuredImagePreview || formData.featured_image) && (
                <div className="mb-4 relative inline-block max-w-full">
                  <img
                    src={featuredImagePreview || formData.featured_image}
                    alt="Featured preview"
                    className="max-w-full h-48 object-cover rounded-xl border-2 border-gray-300 shadow-md"
                  />
                  <button
                    type="button"
                    onClick={clearFeaturedImage}
                    className="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition-colors shadow-lg"
                    title="Remove image"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              )}

              <input
                ref={fileInputRef}
                type="file"
                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                onChange={handleFileChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm"
              />
              <p className="mt-2 text-xs text-gray-500">
                {blog.imageUploadHint || 'Upload an image (max 5MB) or use URL below'}
              </p>

              <div className="mt-3">
                <label className="block text-xs font-semibold text-gray-600 mb-2">
                  {blog.orUseUrl || 'Or use image URL:'}
                </label>
                <input
                  type="url"
                  name="featured_image"
                  value={formData.featured_image}
                  onChange={handleInputChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm"
                  placeholder="https://example.com/image.jpg"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  {blog.statusLabel || 'Status'} <span className="text-red-500">*</span>
                </label>
                <select
                  name="status"
                  value={formData.status}
                  onChange={handleInputChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 font-medium"
                >
                  <option value="draft">{blog.draft || 'Draft'}</option>
                  <option value="published">{blog.published || 'Published'}</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  {blog.publishedAtLabel || 'Published At'}
                </label>
                <input
                  type="datetime-local"
                  name="published_at"
                  value={formData.published_at}
                  onChange={handleInputChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                />
              </div>
            </div>

            <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 border-t border-gray-200">
              <Link
                to="/admin/blog"
                className="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 font-semibold hover:bg-gray-50 transition-colors text-center"
              >
                {blog.cancel || 'Cancel'}
              </Link>
              <button
                type="submit"
                disabled={isSubmitting}
                className="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-semibold hover:from-purple-700 hover:to-pink-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-all duration-200 shadow-lg hover:shadow-xl"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="h-5 w-5 animate-spin" />
                    {blog.saving || 'Saving...'}
                  </>
                ) : (
                  <>
                    <Save className="h-5 w-5" />
                    {blog.save || 'Save'}
                  </>
                )}
              </button>
            </div>
          </form>
        )}
      </div>
    </AdminLayout>
  );
}
