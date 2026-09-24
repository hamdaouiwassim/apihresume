/**
 * Guest resume draft (utils/guestResumeDraft.js) + ClaimGuestResume.jsx flow.
 * The draft lives in localStorage under the same key as before, so drafts started on the old SPA still work.
 */
export const DRAFT_KEY = 'hresume_guest_resume_draft_v1';

export function saveGuestResumeDraft({ name, template_id }) {
    const payload = {
        name: String(name || '').trim(),
        template_id: template_id != null ? String(template_id) : '',
        savedAt: Date.now(),
    };
    if (!payload.name || !payload.template_id) throw new Error('Guest draft requires name and template_id');
    localStorage.setItem(DRAFT_KEY, JSON.stringify(payload));
}

export function peekGuestResumeDraft() {
    try {
        const data = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null');
        return data?.name && data?.template_id ? data : null;
    } catch {
        return null;
    }
}

export default function register(Alpine) {
    window.guestResumeDraft = { save: saveGuestResumeDraft, peek: peekGuestResumeDraft, clear: () => localStorage.removeItem(DRAFT_KEY) };

    Alpine.data('claimDraft', () => ({
        async init() {
            const draft = peekGuestResumeDraft();
            if (!draft) {
                window.location.replace('/resumes');
                return;
            }
            try {
                const response = await window.api.post('resumes', { name: draft.name, template_id: draft.template_id });
                const id = response?.data?.data?.id;
                if (!id) throw new Error('Missing resume id');
                localStorage.removeItem(DRAFT_KEY);
                window.toast.next('success', 'Your resume was created from your draft.');
                window.location.replace(`/resume/edit/${id}`);
            } catch (e) {
                const code = e.response?.data?.code;
                window.toast.next('error', e.response?.data?.message || 'Could not create your resume. Please try again from Create resume.');
                window.location.replace(code === 'resume_limit_reached' ? '/resumes' : '/resume/create');
            }
        },
    }));
}
