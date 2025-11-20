# Production Deployment Guide – Dompdf PDF Generation

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

