This is Free & Open Source API for HResume CV builder 

## Requirement 

- PHP 8.2 

## How to install the project 

- run : composer install
- rename .env.example to .env 
- update the database configuration
- run : php artisan migrate
- run : php artisan key:generate  
- run : php artisan serve

## GitHub project import (resume editor)

The **Fill from GitHub** action calls GitHub’s REST API from the server (`POST /api/resumes/{resume}/github-repo-preview`). Authentication is chosen in this order:

1. **Per-user OAuth** (recommended for private repos the user can access) — user connects GitHub in the app profile. Endpoints: `GET /api/auth/github/import/url`, `GET /api/auth/github/import/callback` (public), `POST /api/auth/github/import/disconnect`. Configure a [GitHub OAuth App](https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/creating-an-oauth-app) with callback URL matching `GITHUB_REDIRECT_URL` (default `${APP_URL}/api/auth/github/import/callback`). The app requests the broad `repo` scope; document this clearly to users.
2. **`GITHUB_TOKEN`** — optional classic personal access token for a server-wide fallback (rate limits + any repositories that token can read). See [GitHub rate limits](https://docs.github.com/en/rest/using-the-rest-api/rate-limits-for-the-rest-api).
3. **Anonymous** — public repositories only, lower rate limits.

Other env:

- `THROTTLE_GITHUB_IMPORT_PER_MINUTE` — preview endpoint (default 20); see `config/rate-limit.php`.
- `THROTTLE_OAUTH_GITHUB_IMPORT_URL_PER_MINUTE` — starting the GitHub OAuth flow (default 20).
