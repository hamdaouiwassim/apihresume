import { adminList } from './common';
import { mix } from '../../ui/mix';

/** Port of pages/admin/TemplatesManagement.jsx */
export default function register(Alpine) {
    Alpine.data('adminTemplates', () =>
        mix(adminList({ endpoint: 'admin/templates', errorMessage: 'Failed to load templates' }), {
            search: '',
            category: 'All',
            modal: null, // null | 'new' | 'edit'
            editing: null,
            deleting: false,
            form: { name: '', description: '', category: 'Corporate' },
            file: null,
            preview: '',
            errors: {},
            init() {
                this.$watch('search', () => this.load(1));
                this.$watch('category', () => this.load(1));
                this.load(1);
            },
            params() {
                return { ...(this.search && { search: this.search }), ...(this.category !== 'All' && { category: this.category }) };
            },
            categoryColor(c) {
                return { Corporate: 'bg-blue-100 text-blue-700', Creative: 'bg-purple-100 text-purple-700' }[c] || 'bg-gray-100 text-gray-700';
            },
            resetPreview(url = '') {
                if (this.preview && this.preview.startsWith('blob:')) URL.revokeObjectURL(this.preview);
                this.preview = url;
            },
            openNew() {
                this.form = { name: '', description: '', category: 'Corporate' };
                this.file = null;
                this.resetPreview('');
                this.errors = {};
                this.editing = null;
                this.modal = 'new';
            },
            openEdit(template) {
                this.form = { name: template.name || '', description: template.description || '', category: template.category || 'Corporate' };
                this.file = null;
                this.resetPreview(template.preview_image_url || '');
                this.errors = {};
                this.editing = template;
                this.modal = 'edit';
            },
            close() {
                this.modal = null;
                this.editing = null;
            },
            onFile(event) {
                const file = event.target.files?.[0] || null;
                this.file = file;
                this.resetPreview(file ? URL.createObjectURL(file) : this.editing?.preview_image_url || '');
                if (this.errors.preview_image) this.errors.preview_image = null;
            },
            async save() {
                const errors = {};
                if (!this.form.name.trim()) errors.name = 'Name is required';
                if (!this.form.category) errors.category = 'Category is required';
                if (!this.file && this.modal !== 'edit') errors.preview_image = 'Preview image is required';
                if (Object.keys(errors).length) {
                    this.errors = errors;
                    return;
                }
                try {
                    const payload = new FormData();
                    payload.append('name', this.form.name);
                    payload.append('description', this.form.description);
                    payload.append('category', this.form.category);
                    if (this.file) payload.append('preview_image', this.file);
                    const headers = { 'Content-Type': 'multipart/form-data' };
                    if (this.modal === 'edit' && this.editing) {
                        await window.api.post(`admin/templates/${this.editing.id}?_method=PUT`, payload, { headers });
                        window.toast.success('Template updated successfully');
                    } else {
                        await window.api.post('admin/templates', payload, { headers });
                        window.toast.success('Template created successfully');
                    }
                    this.close();
                    this.resetPreview('');
                    this.errors = {};
                    this.load(1);
                } catch (error) {
                    if (error.response?.data?.errors) {
                        this.errors = Object.fromEntries(Object.entries(error.response.data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                    }
                    window.toast.error(error.response?.data?.message || 'Failed to save template');
                }
            },
            async remove(template) {
                const ok = await window.confirmDialog({
                    title: 'Delete Template',
                    message: 'Are you sure you want to delete this template? This action cannot be undone.',
                    itemName: template.name,
                    confirmText: 'Yes, Delete',
                    cancelText: 'Cancel',
                });
                if (!ok) return;
                this.deleting = true;
                try {
                    await window.api.delete(`admin/templates/${template.id}`);
                    window.toast.success('Template deleted successfully');
                    this.items = this.items.filter((t) => t.id !== template.id);
                } catch (error) {
                    window.toast.error(error.response?.data?.message || 'Failed to delete template');
                } finally {
                    this.deleting = false;
                }
            },
        }),
    );
}
