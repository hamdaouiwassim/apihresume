export const SITE_ORIGIN = 'https://hresume.pro';

export const DEFAULT_OG_IMAGE = `${SITE_ORIGIN}/og-image.png`;

/**
 * Resolve relative or protocol-relative URLs for Open Graph (requires absolute URLs).
 */
export function toAbsoluteUrl(url) {
  if (!url || typeof url !== 'string') {
    return DEFAULT_OG_IMAGE;
  }
  const trimmed = url.trim();
  if (/^https?:\/\//i.test(trimmed)) {
    return trimmed;
  }
  if (trimmed.startsWith('//')) {
    return `https:${trimmed}`;
  }
  if (trimmed.startsWith('/')) {
    return `${SITE_ORIGIN}${trimmed}`;
  }
  return `${SITE_ORIGIN}/${trimmed}`;
}

export function excerptFromHtml(html, maxLen = 160) {
  const text = (html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  if (!text) {
    return '';
  }
  if (text.length <= maxLen) {
    return text;
  }
  return `${text.slice(0, maxLen - 1)}…`;
}
