/**
 * Shared list behaviour for CoverLetters.jsx and WorkCertificates.jsx: delete with confirm + PDF download.
 */
export async function downloadPdf(url, filename, params = {}) {
    const response = await window.api.get(url, { responseType: 'blob', params });
    const href = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = href;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => window.URL.revokeObjectURL(href), 1000);
}

export default function register(Alpine) {
    window.downloadPdf = downloadPdf;

    Alpine.data('documentList', ({ ids = [], strings = {} } = {}) => ({
        ids,
        deleted: [],
        get count() {
            return this.ids.filter((id) => !this.deleted.includes(id)).length;
        },
        isVisible(id) {
            return !this.deleted.includes(id);
        },
        async remove(endpoint, id, name) {
            const ok = await window.confirmDialog({
                title: strings.confirmTitle,
                message: strings.confirmMessage,
                itemName: name,
                confirmText: strings.confirmText,
                cancelText: strings.cancelText,
            });
            if (!ok) return;
            try {
                await window.api.delete(`${endpoint}/${id}`);
                window.toast.success(strings.deleteSuccess);
                this.deleted.push(id);
            } catch (error) {
                window.toast.error(error.response?.data?.message || strings.deleteError);
            }
        },
        async download(url, title, params = {}) {
            try {
                await downloadPdf(url, `${String(title || 'document').replace(/\s+/g, '_')}.pdf`, params);
            } catch {
                window.toast.error(strings.pdfError);
            }
        },
    }));
}
