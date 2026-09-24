/**
 * Strip HTML to plain text (for AI enhance, validation).
 */
export function htmlToPlainText(html) {
  if (!html || typeof html !== 'string') return '';
  if (typeof document !== 'undefined') {
    const el = document.createElement('div');
    el.innerHTML = html;
    return (el.textContent || el.innerText || '').replace(/\s+/g, ' ').trim();
  }
  return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

export function isBlogContentEmpty(html) {
  return htmlToPlainText(html).length === 0;
}

function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * Convert plain text (e.g. from AI enhance) to simple blog HTML paragraphs.
 */
export function plainTextToBlogHtml(text) {
  const trimmed = (text || '').trim();
  if (!trimmed) return '';

  return trimmed
    .split(/\n\n+/)
    .map((block) => {
      const inner = block
        .trim()
        .split('\n')
        .map((line) => escapeHtml(line))
        .join('<br>');
      return `<p>${inner}</p>`;
    })
    .join('');
}
