import { adminList } from './common';
import { mix } from '../../ui/mix';

/** Port of pages/admin/CoverLetterTemplatesManagement.jsx */
export default function register(Alpine) {
    Alpine.data('adminClTemplates', () =>
        mix(adminList({ endpoint: 'admin/cover-letter-templates', errorMessage: 'Failed to load templates' }), {
            search: '',
            language: 'All',
            modal: null,
            editing: null,
            saving: false,
            form: { name: '', job_type: '', language: 'en', subject: '', content: '', is_active: true },
            errors: {},
            init() {
                this.$watch('search', () => this.load(1));
                this.$watch('language', () => this.load(1));
                this.load(1);
            },
            params() {
                return { ...(this.search && { search: this.search }), ...(this.language !== 'All' && { language: this.language }) };
            },
            openNew() {
                this.form = { name: '', job_type: '', language: window.__APP__?.locale === 'fr' ? 'fr' : 'en', subject: '', content: '', is_active: true };
                this.errors = {};
                this.editing = null;
                this.modal = 'new';
            },
            openEdit(t) {
                this.form = {
                    name: t.name || '',
                    job_type: t.job_type || '',
                    language: t.language || 'en',
                    subject: t.subject || '',
                    content: t.content || '',
                    is_active: t.is_active ?? true,
                };
                this.errors = {};
                this.editing = t;
                this.modal = 'edit';
            },
            close() {
                this.modal = null;
                this.editing = null;
            },
            async save() {
                this.saving = true;
                try {
                    if (this.modal === 'edit' && this.editing) {
                        await window.api.put(`admin/cover-letter-templates/${this.editing.id}`, this.form);
                        window.toast.success('Template updated successfully');
                    } else {
                        await window.api.post('admin/cover-letter-templates', this.form);
                        window.toast.success('Template created successfully');
                    }
                    this.close();
                    this.load(1);
                } catch (error) {
                    if (error.response?.data?.errors) this.errors = error.response.data.errors;
                    window.toast.error(error.response?.data?.message || 'Failed to save template');
                } finally {
                    this.saving = false;
                }
            },
            async remove(t) {
                const ok = await window.confirmDialog({
                    title: 'Delete Template',
                    message: `Are you sure you want to delete "${t.name}"? This action cannot be undone.`,
                    itemName: t.name,
                    confirmText: 'Yes, Delete',
                    cancelText: 'Cancel',
                });
                if (!ok) return;
                try {
                    await window.api.delete(`admin/cover-letter-templates/${t.id}`);
                    window.toast.success('Template deleted successfully');
                    this.items = this.items.filter((x) => x.id !== t.id);
                } catch (error) {
                    window.toast.error(error.response?.data?.message || 'Failed to delete template');
                }
            },
        }),
    );
}
