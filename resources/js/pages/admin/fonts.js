/** Port of pages/admin/FontManagement.jsx */
export default function register(Alpine) {
    Alpine.data('adminFonts', () => ({
        fonts: [],
        loading: true,
        uploading: false,
        showForm: false,
        familyName: '',
        files: { regular: null, bold: null, italic: null, bold_italic: null },
        variants: ['regular_path', 'bold_path', 'italic_path', 'bold_italic_path'],
        init() {
            this.fetch();
        },
        variantLabel(v) {
            return { regular_path: 'Regular', bold_path: 'Bold', italic_path: 'Italic', bold_italic_path: 'Bold Italic' }[v] || v;
        },
        async fetch() {
            this.loading = true;
            try {
                const { data } = await window.api.get('admin/fonts');
                if (data.status) this.fonts = data.data;
            } catch {
                window.toast.error('Failed to load fonts');
            } finally {
                this.loading = false;
            }
        },
        onFile(variant, event) {
            const file = event.target.files[0];
            if (!file) return;
            const ext = file.name.split('.').pop().toLowerCase();
            if (!['ttf', 'otf'].includes(ext)) {
                window.toast.error('Only .ttf and .otf files are accepted');
                event.target.value = '';
                return;
            }
            this.files[variant] = file;
        },
        async upload() {
            if (!this.familyName.trim()) return window.toast.error('Font family name is required');
            if (!this.files.regular) return window.toast.error('Regular font file is required');
            this.uploading = true;
            try {
                const form = new FormData();
                form.append('family_name', this.familyName.trim());
                Object.entries(this.files).forEach(([k, f]) => f && form.append(k, f));
                const { data } = await window.api.post('admin/fonts', form, { headers: { 'Content-Type': 'multipart/form-data' }, skipValidationToast: true });
                if (data.status) {
                    window.toast.success('Font uploaded successfully!');
                    this.familyName = '';
                    this.files = { regular: null, bold: null, italic: null, bold_italic: null };
                    this.showForm = false;
                    this.fetch();
                }
            } catch (error) {
                window.toast.error(error.response?.data?.message || error.response?.data?.errors?.family_name?.[0] || 'Failed to upload font');
            } finally {
                this.uploading = false;
            }
        },
        async toggle(font) {
            try {
                const { data } = await window.api.post(`admin/fonts/${font.id}/toggle`);
                if (data.status) {
                    window.toast.success(data.message);
                    font.is_active = !font.is_active;
                }
            } catch {
                window.toast.error('Failed to toggle font');
            }
        },
        async remove(font) {
            if (!window.confirm(`Delete font "${font.family_name}"? This cannot be undone.`)) return;
            try {
                const { data } = await window.api.delete(`admin/fonts/${font.id}`);
                if (data.status) {
                    window.toast.success('Font deleted');
                    this.fonts = this.fonts.filter((f) => f.id !== font.id);
                }
            } catch {
                window.toast.error('Failed to delete font');
            }
        },
    }));
}
