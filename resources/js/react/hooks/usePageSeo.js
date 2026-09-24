import { useEffect } from 'react';
import { DEFAULT_OG_IMAGE, SITE_ORIGIN, toAbsoluteUrl } from '../utils/seo';

const OG_PROPERTIES = [
  'og:title',
  'og:description',
  'og:url',
  'og:image',
  'og:type',
  'og:image:alt',
];

const TWITTER_NAMES = [
  'twitter:title',
  'twitter:description',
  'twitter:url',
  'twitter:image',
];

function readMeta(attr, key) {
  const selector = attr === 'property' ? `meta[property="${key}"]` : `meta[name="${key}"]`;
  return document.querySelector(selector)?.getAttribute('content') ?? '';
}

function writeMeta(attr, key, value) {
  const selector = attr === 'property' ? `meta[property="${key}"]` : `meta[name="${key}"]`;
  let el = document.querySelector(selector);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute(attr, key);
    document.head.appendChild(el);
  }
  el.setAttribute('content', value || '');
}

/**
 * Updates document title, description, canonical, and Open Graph / Twitter tags.
 * Restores previous values on unmount.
 */
export function usePageSeo({
  title,
  description,
  canonicalPath,
  image,
  imageAlt,
  ogType = 'website',
  enabled = true,
}) {
  useEffect(() => {
    if (!enabled || !title) {
      return undefined;
    }

    const prevTitle = document.title;
    const meta = document.querySelector('meta[name="description"]');
    const prevDesc = meta?.getAttribute('content') ?? '';
    const canonical = document.querySelector('link[rel="canonical"]');
    const prevCanonical = canonical?.getAttribute('href') ?? '';

    const prevOg = Object.fromEntries(OG_PROPERTIES.map((key) => [key, readMeta('property', key)]));
    const prevTwitter = Object.fromEntries(TWITTER_NAMES.map((key) => [key, readMeta('name', key)]));

    const canonicalUrl = canonicalPath ? `${SITE_ORIGIN}${canonicalPath}` : SITE_ORIGIN;
    const ogImage = toAbsoluteUrl(image || DEFAULT_OG_IMAGE);
    const safeDescription = description || '';
    const safeImageAlt = imageAlt || title;

    document.title = title;
    if (meta && safeDescription) {
      meta.setAttribute('content', safeDescription);
    }
    if (canonical && canonicalPath) {
      canonical.setAttribute('href', canonicalUrl);
    }

    writeMeta('property', 'og:title', title);
    writeMeta('property', 'og:description', safeDescription);
    writeMeta('property', 'og:url', canonicalUrl);
    writeMeta('property', 'og:image', ogImage);
    writeMeta('property', 'og:type', ogType);
    writeMeta('property', 'og:image:alt', safeImageAlt);
    writeMeta('name', 'twitter:title', title);
    writeMeta('name', 'twitter:description', safeDescription);
    writeMeta('name', 'twitter:url', canonicalUrl);
    writeMeta('name', 'twitter:image', ogImage);

    return () => {
      document.title = prevTitle;
      if (meta) {
        meta.setAttribute('content', prevDesc);
      }
      if (canonical) {
        canonical.setAttribute('href', prevCanonical);
      }
      for (const key of OG_PROPERTIES) {
        writeMeta('property', key, prevOg[key]);
      }
      for (const key of TWITTER_NAMES) {
        writeMeta('name', key, prevTwitter[key]);
      }
    };
  }, [title, description, canonicalPath, image, imageAlt, ogType, enabled]);
}
