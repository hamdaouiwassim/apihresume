const STORAGE_KEY = "hresume_guest_resume_draft_v1";

/**
 * Persist a resume name + template choice before the user signs in.
 * Cleared after successful claim via {@link consumeGuestResumeDraft}.
 */
export function saveGuestResumeDraft({ name, template_id }) {
  const payload = {
    name: String(name || "").trim(),
    template_id: template_id != null ? String(template_id) : "",
    savedAt: Date.now(),
  };
  if (!payload.name || !payload.template_id) {
    throw new Error("Guest draft requires name and template_id");
  }
  localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
}

export function peekGuestResumeDraft() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const data = JSON.parse(raw);
    if (!data?.name || !data?.template_id) return null;
    return data;
  } catch {
    return null;
  }
}

export function consumeGuestResumeDraft() {
  const data = peekGuestResumeDraft();
  if (data) {
    localStorage.removeItem(STORAGE_KEY);
  }
  return data;
}

export function clearGuestResumeDraft() {
  localStorage.removeItem(STORAGE_KEY);
}
