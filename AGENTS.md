# AGENTS.md — Cantina Sant'Anna ERP

## Project Type

PHP 8.0+ vanilla app (no framework, no Composer, no npm). SQLite database. Runs on Apache/Laragon.

## Key Architecture Facts

- **Namespace:** `CantinaFinanceiro` — all classes in `src/`
- **No autoloader** — every PHP page uses `require_once` for each class it needs
- **No ORM** — raw PDO with prepared statements everywhere
- **All models are static** — `Auth::checkAuth()`, `Database::getConnection()`, `AccountModel::getAll()`, etc.
- **Page controllers** — each `.php` file at root is a self-contained page (logic + HTML)
- **Shared includes** in `src/includes/` — `layout_start.php` wraps every page with `sidebar.php`, `mobile_header.php`, `alerts.php`, `scripts_footer.php`. Sidebar edited once, reflects everywhere.

## Database

- SQLite file: `db/cantina.sqlite`
- Auto-created on first request if missing
- Schema in `src/Database.php` — runs `CREATE TABLE IF NOT EXISTS` + `ALTER TABLE` migrations
- **All monetary values are INTEGER cents** — never store floats. Use `parseBrlToCents()` pattern.
- Foreign keys enforced via `PRAGMA foreign_keys = ON`

## Auth & RBAC

- Sessions with `httponly`, `samesite=Strict`, `secure` (if HTTPS)
- Four roles: `admin`, `gerente`, `operador`, `nutricionista`
- Check: `Auth::checkAuth()` then `Auth::restrictTo(['gerente', 'operador'])`
- Admin-only pages: `admin.php`
- Gerente-only pages: `usuarios.php`
- Nutricionista-only pages: `cardapios.php` (only page they can access)
- Default creds: `admin/admin123`, `gerente/gerente123`, `nutricionista/nutricionista123` — change immediately
- Login redirect: nutricionista goes to `cardapios.php`; all others go to `index.php`

## Adding New Features

1. Create model class in `src/` (extend existing pattern: static methods, PDO from `Database::getConnection()`)
2. Add `require_once __DIR__ . '/src/YourModel.php';` at top of page controller
3. Add table schema in `src/Database.php` inside `initializeSchema()` with `CREATE TABLE IF NOT EXISTS`
4. Add audit logging: `Auth::logAction($userId, 'ACTION_CODE', 'Details')`

## Styling

- Bootstrap 5.3.0 via CDN
- Custom CSS: `assets/css/style.css` — glassmorphism, CSS variables, premium buttons
- Primary color: `#4f46e5` (indigo), accent gold: `--accent-gold: #d4a853`
- Font: Google Fonts "Outfit"
- Classes: `.card-glass`, `.btn-premium`, `.form-control-premium`, `.table-premium`
- **Warm Luxury theme:** Sidebar dark gradient (`#0f0a1e → #1a1035 → #1f1440`) with gold `#d4a853` accents — profile avatar, section title dividers, nav-link active states with gold left border and glow
- Sidebar classes: `.sidebar-panel`, `.sidebar-brand`, `.sidebar-profile`, `.sidebar-profile-avatar`, `.nav-section-title`, `.section-line`, `.sidebar-logout`, `.sidebar-footer`
- Mobile: `.sidebar-mobile-header` (dark gradient matching sidebar), `.sidebar-mobile-toggle`, `.sidebar-mobile-dropdown` (dark dropdown with gold hover)

## JS Patterns

- No build step — vanilla JS, loaded via `<script>` tags
- AJAX uses `fetch()` returning JSON (`cardapio_action.php` is the pattern)
- Money mask: `parseBrlToCents()` / `formatBrl()` pattern on inputs
- Bootstrap modals for forms (not separate pages)
- Page-specific JS in `assets/js/pagina.js` (e.g. `cardapios.js`), loaded via `<script src="...">` in the page controller
- Shared JS utilities in `assets/js/toast.js` and `assets/js/utils.js` (loaded via `scripts_footer.php`)
- PHP data passed to JS via `data-*` attributes on HTML elements, not inline `<script>` blocks

## PDF Export

- `html2pdf.js` v0.10.1 via CDN
- Creates temporary `<div>`, appends to body, generates PDF, removes div
- A4 landscape: `jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }`

## External APIs

- **OpenRouter** — AI menu generation (synchronous cURL, 80s timeout)
- **MailGrid** — Email sending (cURL POST, 15s timeout)

## Security Notes

- `db/.htaccess` and `backups/.htaccess` deny all HTTP access
- Cron file has randomized name: `cron_daily_notifications_4fb9e2.php`
- No CSRF tokens implemented (forms use POST only)
- All output escaped with `htmlspecialchars()`

## Gotchas

- The `cardapio_action.php` controller handles all menu AJAX — check action names carefully
- `AccountModel::yieldAll()` is a generator — iterate with `foreach`, not `fetchAll()`
- Backup files are ZIP containing `cantina.sqlite` — restore copies to `db/cantina.sqlite`
- Sidebar nav is centralized in `src/includes/sidebar.php` — add new menu items there only
- The `cardapios.php` page JS is in `assets/js/cardapios.js` (not inline) — page HTML passes data via `data-*` attributes
