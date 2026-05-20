# Repository Documentation Guide

Purpose
-------
This document is a guide for the EasyDash project. Branched from the "laravel12-stisla" template. It summarizes prerequisites, setup steps, project layout, development workflow, deployment notes, and troubleshooting tips.

**Quick Facts**
- **Framework:** Laravel 12
- **PHP:** >= 8.2
- **Frontend template:** Stisla (Bootstrap 5)
- **Auth scaffolding:** Laravel Breeze

Tech Stack
-------------
- Laravel + Blade, Composer, Node.js, HTML, CSS, PHP, SQLite (or other database service), Javascript, Typescript, Leaflet, Select2, React, Excalidraw, Git


Quick Setup (Local)
-------------------
1. Clone repository

```bash
git clone <REPO_URL> project
cd project
```

2. Install PHP dependencies

```bash
composer install
```

3. Install Node dependencies and build assets (dev)

```bash
npm install
npm run dev
```

4. Copy environment file and generate app key

```bash
cp .env.example .env
php artisan key:generate
```

5. Configure `.env` database and mail settings (see Environment section)

6. Run migrations and optional seeders

```bash
php artisan migrate
php artisan db:seed
```

7. Start local server

```bash
php artisan serve
```

Environment
-----------
- Keep sensitive credentials out of the repo. Use `.env` for local development. Ensure `.env.example` is accurate and up to date.
- Important variables: `APP_ENV`, `APP_KEY`, `APP_URL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `MAIL_*`.

Project Structure (high level)
------------------------------
- **artisan**: Laravel CLI entry
- **app/**: Core PHP app code (models, controllers, middleware, providers)
- **config/**: Configuration files
- **database/**: Migrations, factories, seeders
- **public/**: Webroot, Stisla assets, compiled frontend (CSS/JS)
- **resources/**: Blade views, JS/CSS source, lang files
- **routes/**: `web.php`, `console.php` routing definitions
- **tests/**: PHPUnit tests
- **vite-project/**: Frontend project files (if used for the Stisla integration)
- **vendor/**: Composer packages

Key files and locations
-----------------------
- App logic (models & dashboard): [app/Models/Dashboard.php](app/Models/Dashboard.php), [app/Models/DashboardWidget.php](app/Models/DashboardWidget.php), [app/Models/DashboardWidgetType.php](app/Models/DashboardWidgetType.php)
- Controllers: [app/Http/Controllers](app/Http/Controllers)
- Routes: [routes/web.php](routes/web.php)
- Views & Blade templates: [resources/views](resources/views)
- Public template assets (Stisla): [public/assets](public/assets) and [public/css](public/css), [public/js](public/js)
- Migrations: [database/migrations](database/migrations)
- Seeders: [database/seeders](database/seeders)
- Composer manifest: [composer.json](composer.json)
- Node manifest: [package.json](package.json)
- README and project intro: [README.MD](README.MD)
- MetaData for files: Stored in Dashboard Controller

Development Workflow
--------------------
- Branching: follow GitFlow or the team's chosen branching model.
- Code style: follow existing project style. Run `npm run lint` if configured.
- Frontend: modify sources under `resources/js` / `vite-project/src` and re-run `npm run dev` or `npm run build`.
- Caching: when changing config or routes, run `php artisan config:cache` and `php artisan route:cache` for production.

Testing
-------
- Run PHPUnit tests:

```bash
./vendor/bin/phpunit
# or
php artisan test
```

Frontend build (production)
---------------------------

```bash
npm run build
```

Deployment Notes
----------------
- Typical production steps:
  - Ensure `.env` variables set and secured
  - `composer install --no-dev --optimize-autoloader`
  - `npm ci && npm run build` (or use CI to build assets)
  - `php artisan migrate --force`
  - `php artisan config:cache && php artisan route:cache && php artisan view:cache`
  - Ensure proper webserver configuration (document root -> `public/`)

Common Troubleshooting
----------------------
- Composer fails: update Composer version and clear caches: `composer clear-cache`
- NPM build errors: delete `node_modules` and re-run `npm ci` or `npm install`
- Permissions on `storage/` and `bootstrap/cache/`: set writable by web server
- Database migration errors: check `.env` DB settings and run migrations with `--step` to isolate

Where Stisla and UI live
------------------------
- Stisla template files and compiled assets are in `public/` and `resources/`. To update UI elements, edit Blade templates under `resources/views` and the SCSS/JS sources under `resources/` or `vite-project/` depending on which area your change touches.

Dashboard & Authentication
--------------------------
- Authentication uses Laravel Breeze; relevant routes and views are in `routes/web.php` and `resources/views/auth` (or Breeze default structure).
- Dashboard-related logic is grouped in `app/Models/*` and corresponding controllers under `app/Http/Controllers`.

Handoff Checklist
-----------------

- **Outstanding Issues:**
Multi-Language Support
Website palette changer
Refinement of drag-and-drop logic and implemented features
Streamline widget creation
More interconnectivity of widgets
Detailed color control of points on map
Other methods of data importing and data formats
Limiting file layers for map per dashboard
More GIS features
Postgres “update data” feature
Bug fixes
Export to Dr. Lembo for hosting
Making the program an executable?
Book mark instances of widgets?

- **Contacts:** Dr. Jing has the repo and should have our group's most up to date contact information. Link for repo for project: https://github.com/kakujodai/laravel12-stisla
Just in case though, Layla Phipps(Graduating, lphipps2@gulls.salisbury.edu), Emilee Breckenridge (ebreckenridge1@gulls.salisbury.edu), Thaddeus Versteegen (Graduating, tversteegen1@gulls.salisbury.edu), Connor Dailey(cdailey1@gulls.salisbury.edu) worked on the project for the 2025-6 school year.
See original template contributors in [README.MD](README.MD).

Appendix — Useful Commands
-------------------------
- Install PHP deps: `composer install`
- Install JS deps: `npm install`
- Build dev assets: `npm run dev`
- Build prod assets: `npm run build`
- Run migrations: `php artisan migrate`
- Run tests: `php artisan test`


