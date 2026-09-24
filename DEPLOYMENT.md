# Production Deployment Guide

## Single app: Blade pages + JSON API (replaces the separate React frontend)

The whole site is now served by this Laravel app. Pages are Blade views (with Alpine.js); the resume,
cover letter, work certificate and blog editors, the shared resume view and a few admin screens are
React "islands" mounted inside Blade pages. The React SPA in `hresume_frontend/` is no longer deployed.

### Build and deploy
```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build              # Vite: Tailwind CSS, Alpine app, React islands -> public/build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Node is only needed at build time (you can build in CI and upload `public/build`).

### Environment changes at cutover (main domain now points to Laravel)
- `APP_URL=https://hresume.pro` and `FRONTEND_APP_URL=https://hresume.pro` (same origin now).
  OAuth redirects, Stripe/Paddle success URLs, canonical URLs and the sitemap use `FRONTEND_APP_URL`.
- `SANCTUM_STATEFUL_DOMAINS=hresume.pro,www.hresume.pro` (the site's own host) so the pages' same-origin
  API calls use the session cookie.
- `SESSION_DOMAIN=` (empty) and `SESSION_SAME_SITE=lax`, `SESSION_SECURE_COOKIE=true`. A leftover
  `SESSION_DOMAIN` from the old API host (e.g. `apihresume.hamdaouiacademy.com`) makes browsers drop the
  session cookie: every login (password, Google, LinkedIn) bounces back to `/login` or fails with
  "CSRF token mismatch". Check with `curl -sI https://hresume.pro/login | grep -i set-cookie`.
- `CORS_ALLOWED_ORIGINS` is no longer needed for the site itself.
- Update the OAuth apps (Google, LinkedIn, GitHub import) callback URLs to the new API host if it changed:
  `https://hresume.pro/api/auth/google/callback`, `.../api/auth/linkedin/callback`, `.../api/auth/github/import/callback`.
- Optional landing options (formerly Vite env vars): `LANDING_HERO_VARIANT`, `WALKTHROUGH_VIDEO_URL`, `SHOW_WALKTHROUGH_SECTION`.

### Blog image optimization
Uploaded blog featured images are converted to WebP at 480, 800 and 1200 px wide (never upscaled).
Blog pages serve them with `srcset`, real `width`/`height` and a preload for the article image.
Requires the PHP GD extension with WebP support. After deploying, convert images uploaded earlier once:
```bash
php artisan migrate --force
php artisan blog:optimize-images --dry-run            # list what will change
php artisan blog:optimize-images --delete-originals   # convert, then remove the original files
```
Images added by external URL are left as they are.

### Self-hosted assets (no CDNs)
- JS/CSS libraries (Alpine, React, Tiptap, pdf.js + worker, axios, Lucide icons) are bundled by Vite into `public/build`.
- Fonts: `public/fonts/inter` (Pulse admin login) and `public/fonts/figtree` (Pulse dashboard, overridden in
  `resources/views/vendor/pulse/components/pulse.blade.php`). The site itself uses the system font stack.
- Default avatars: `public/images/avatars/*.svg` (`default_avatar()` in PHP, `window.defaultAvatar()` in JS).
- Image fallbacks: `/placeholder/{w}x{h}?bg=&fg=&text=` generates an SVG locally.
- Still external by nature: Google Analytics, OAuth providers, Stripe/Paddle checkout, AI APIs, the optional
  YouTube walkthrough embed, and user avatars that come from Google/LinkedIn sign-in.

### Static file caching and compression
- `npm run build` outputs minified JS/CSS (no source maps) with hashed file names, plus a `.gz` copy of every
  text asset over 1 KB (e.g. `app.css` 177 KB -> 22 KB).
- nginx: include `deploy/nginx/hresume-static.conf` inside the site's `server {}` block, before `location /`:
  ```nginx
  include /var/www/html/hresume/api/deploy/nginx/hresume-static.conf;
  ```
  then `sudo nginx -t && sudo systemctl reload nginx`. It sets:
  - `/build/assets/*` and `/fonts/*`: `Cache-Control: public, max-age=31536000, immutable` (hashed names change on each build)
  - `/build/manifest.json`: `no-cache`
  - images/icons (`/images`, favicons, `/storage` uploads): 30 days
  - `gzip_static on` (serves the prebuilt `.gz`) + on-the-fly gzip for HTML/JSON/SVG
- Apache: the same rules are in `public/.htaccess` (needs `mod_headers`, `mod_rewrite`, `mod_deflate`).
- Check: `curl -sI -H 'Accept-Encoding: gzip' https://hresume.pro/build/assets/<file>.css` shows
  `Cache-Control: ... immutable` and `Content-Encoding: gzip`.
- HTML pages are not cached (they contain the user session and CSRF token).

### Notes
- UI translations live in `resources/translations/{en,fr}.json` (same keys as the old React app); use `t('key')` in Blade.
- The chosen language is stored in the session (`/locale/fr`, `/locale/en`).
- The recruiter role is disabled: its pages and API routes are removed, `/recruiter/*` and `/register/recruiter`
  redirect. Models, migrations and `app/Http/Controllers/Recruiter/*` are kept so it can be re-enabled later.
- Web pages send a CSP that allows `'unsafe-eval'` (required by Alpine.js) and Google Analytics.

---

# PDF generation (Dompdf)

## Overview
The backend now uses **Dompdf** (pure PHP) to render resumes into PDFs. Node.js, Puppeteer, and system browsers are no longer required. Dompdf runs completely inside PHP, which greatly simplifies deployment on Linux servers.

## Requirements
- PHP extensions: `mbstring`, `gd`, `dom`, `json`, `xml`.
- Composer dependency: `dompdf/dompdf` (already listed in `composer.json`).
- Enough memory for large resumes (configure `memory_limit` accordingly, e.g. `256M`).

## Deployment Steps
1. **Install PHP extensions** (Ubuntu example):
   ```bash
   sudo apt-get install -y php8.2-mbstring php8.2-gd php8.2-xml
   sudo systemctl restart php8.2-fpm
   ```

2. **Install composer dependencies** inside `api/`:
   ```bash
   cd /var/www/html/hresume/api
   composer install --no-dev --optimize-autoloader
   ```

3. **Clear caches** after deployment:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## Configuration Notes
- Dompdf needs remote assets enabled (already set in the controller). Ensure any images/CSS referenced by the HTML are reachable via HTTPS.
- If you use custom fonts, add them under `storage/fonts` and register via Dompdf’s font loader or CSS `@font-face`.
- Page margins and defaults are controlled in `PDFController::wrapHtmlDocument()`. Adjust the embedded CSS if you need different margins or base styles.

## Verification
1. Hit the `/generate-pdf` endpoint from the frontend or via curl/Postman with sample HTML.
2. Inspect the generated PDF for layout accuracy.
3. Monitor `storage/logs/laravel.log` for errors (look for entries mentioning `Dompdf`).

## Troubleshooting
- **Blank PDF**: ensure the HTML being sent is valid and includes inline styles; Dompdf ignores unsupported CSS/JS.
- **Images not loading**: confirm the URLs are absolute (https://...) and that `allow_url_fopen` is enabled.
- **Font issues**: embed fonts via `@font-face` or configure Dompdf’s font directory.
- **Out-of-memory**: increase PHP `memory_limit` or simplify the resume template.

With Dompdf the infrastructure no longer depends on Node, Chrome, or nvm. Standard PHP deployments are sufficient.

