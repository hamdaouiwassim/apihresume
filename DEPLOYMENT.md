# Production Deployment Guide - PDF Generation Fix

## Problem
The PDF generation was failing in production with the error:
```
SyntaxError: Unexpected token '?'
```

This occurs because `spatie/browsershot` v5.0 requires Node.js 14+ (for the nullish coalescing operator `??`), but production was using an older Node.js version.

## Solution

### Option 1: Set NODE_BINARY_PATH Environment Variable (Recommended)

In your production `.env` file, add:

```env
NODE_BINARY_PATH=/home/your-user/.nvm/versions/node/v18.20.0/bin/node
```

Replace the path with your actual nvm Node.js path. To find it, run:
```bash
which node
# or
source ~/.nvm/nvm.sh && which node
```

### Option 2: Ensure nvm Node.js is in PATH

Make sure your web server (Apache/Nginx) and PHP-FPM have access to the correct Node.js version. 

For PHP-FPM, you may need to set the PATH in your PHP-FPM pool configuration or in your web server's environment.

**For PHP-FPM (if using systemd):**
Edit `/etc/systemd/system/php-fpm.service` or your PHP-FPM service file and add:
```ini
Environment="PATH=/home/your-user/.nvm/versions/node/v18.20.0/bin:/usr/local/bin:/usr/bin:/bin"
```

**For Apache:**
Add to your virtual host configuration or `/etc/apache2/envvars`:
```apache
SetEnv PATH "/home/your-user/.nvm/versions/node/v18.20.0/bin:/usr/local/bin:/usr/bin:/bin"
```

**For Nginx with PHP-FPM:**
Set in your PHP-FPM pool configuration (`/etc/php/8.2/fpm/pool.d/www.conf`):
```ini
env[PATH] = /home/your-user/.nvm/versions/node/v18.20.0/bin:/usr/local/bin:/usr/bin:/bin
```

### Option 3: Use .nvmrc File

The code will automatically detect Node.js from nvm if:
1. The `.nvmrc` file exists in the `api` directory (already created with version 18)
2. The nvm installation is accessible from the web server user

Make sure to:
```bash
cd /var/www/html/hresume/api
source ~/.nvm/nvm.sh
nvm install 18
nvm use 18
```

## Verification

After deployment, verify the Node.js version:
```bash
# Check Node.js version
node --version  # Should be v14.0.0 or higher

# Test PDF generation
# Make a request to your PDF generation endpoint
```

## Troubleshooting

1. **If the error persists**, check the actual Node.js path being used:
   - Add logging to `PDFController::getNodeBinaryPath()` to see what path is detected
   - Check PHP error logs for any path-related errors

2. **If nvm is not accessible**, you may need to:
   - Install Node.js 14+ globally (not via nvm)
   - Or set up a symlink from `/usr/local/bin/node` to your nvm node binary

3. **For shared hosting**, you may need to:
   - Contact your hosting provider to install Node.js 14+
   - Or use the `NODE_BINARY_PATH` environment variable with the full path

## Code Changes Made

1. Created `.nvmrc` file specifying Node.js 18
2. Updated `PDFController` to automatically detect Node.js from nvm
3. Added support for `NODE_BINARY_PATH` environment variable
4. Added fallback logic to find Node.js 14+ in nvm installations

