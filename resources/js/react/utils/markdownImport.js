import { marked } from 'marked';

const MAX_MD_BYTES = 2 * 1024 * 1024;

marked.setOptions({
  gfm: true,
  breaks: false,
});

/**
 * Parse optional YAML-like frontmatter (--- ... ---).
 * Supports simple `key: value` lines (title, excerpt, description).
 */
export function parseMarkdownFrontmatter(raw) {
  const text = raw.replace(/^\uFEFF/, '');
  if (!text.startsWith('---')) {
    return { meta: {}, body: text };
  }

  const endIndex = text.indexOf('\n---', 3);
  if (endIndex === -1) {
    return { meta: {}, body: text };
  }

  const frontmatter = text.slice(3, endIndex).trim();
  const body = text.slice(endIndex + 4).replace(/^\s*/, '');

  const meta = {};
  for (const line of frontmatter.split('\n')) {
    const match = line.match(/^([a-zA-Z0-9_-]+):\s*(.+)$/);
    if (!match) continue;
    let value = match[2].trim();
    if (
      (value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'"))
    ) {
      value = value.slice(1, -1);
    }
    meta[match[1].toLowerCase()] = value;
  }

  return { meta, body };
}

/**
 * Map frontmatter keys to blog post fields.
 */
export function frontmatterToPostFields(meta) {
  if (!meta || typeof meta !== 'object') return {};
  const title = meta.title?.trim();
  const excerpt = (meta.excerpt || meta.description || meta.summary || '').trim();
  return {
    ...(title ? { title } : {}),
    ...(excerpt ? { excerpt } : {}),
  };
}

/**
 * Convert markdown string to HTML for TipTap / blog storage.
 */
export function markdownToBlogHtml(markdown) {
  const trimmed = (markdown || '').trim();
  if (!trimmed) return '';
  return marked.parse(trimmed);
}

/**
 * Read a .md file and return { html, fields, fileName }.
 */
export async function importMarkdownFile(
  file,
  { existingContentEmpty = true, confirmReplaceMessage } = {}
) {
  if (!file) {
    throw new Error('No file selected');
  }

  const name = (file.name || '').toLowerCase();
  if (!name.endsWith('.md') && !name.endsWith('.markdown') && file.type !== 'text/markdown') {
    const isText = file.type === 'text/plain' || file.type === '';
    if (!isText) {
      throw new Error('Please choose a Markdown (.md) file');
    }
  }

  if (file.size > MAX_MD_BYTES) {
    throw new Error('File is too large (max 2 MB)');
  }

  const raw = await file.text();
  const { meta, body } = parseMarkdownFrontmatter(raw);
  const html = markdownToBlogHtml(body);

  const hasExisting = !existingContentEmpty;
  if (hasExisting) {
    const ok = window.confirm(
      confirmReplaceMessage ||
        'Importing will replace the current editor content. Continue?'
    );
    if (!ok) {
      return { cancelled: true };
    }
  }

  return {
    cancelled: false,
    html,
    fields: frontmatterToPostFields(meta),
    fileName: file.name,
  };
}
