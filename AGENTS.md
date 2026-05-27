# AGENTS.md — Cantina Sant'Anna ERP

## Project Type

PHP 8.0+ vanilla app (no framework, no Composer, no npm). SQLite database. Runs on Apache/Laragon.

## Key Architecture Facts

- **Namespace:** `CantinaFinanceiro` — all classes in `src/`
- **No autoloader** — every PHP page uses `require_once` for each class it needs
- **No ORM** — raw PDO with prepared statements everywhere
- **All models are static** — `Auth::checkAuth()`, `Database::getConnection()`, `AccountModel::getAll()`, etc.
- **Page controllers** — each `.php` file at root is a self-contained page (logic + HTML)
- **Sidebar navigation is duplicated** in every page file (~40 lines each). No shared include.

## Database

- SQLite file: `db/cantina.sqlite`
- Auto-created on first request if missing
- Schema in `src/Database.php` — runs `CREATE TABLE IF NOT EXISTS` + `ALTER TABLE` migrations
- **All monetary values are INTEGER cents** — never store floats. Use `parseBrlToCents()` pattern.
- Foreign keys enforced via `PRAGMA foreign_keys = ON`

## Auth & RBAC

- Sessions with `httponly`, `samesite=Strict`, `secure` (if HTTPS)
- Three roles: `admin`, `gerente`, `operador`
- Check: `Auth::checkAuth()` then `Auth::restrictTo(['gerente', 'operador'])`
- Admin-only pages: `admin.php`
- Gerente-only pages: `usuarios.php`
- Default creds: `admin/admin123`, `gerente/gerente123` — change immediately

## Adding New Features

1. Create model class in `src/` (extend existing pattern: static methods, PDO from `Database::getConnection()`)
2. Add `require_once __DIR__ . '/src/YourModel.php';` at top of page controller
3. Add table schema in `src/Database.php` inside `initializeSchema()` with `CREATE TABLE IF NOT EXISTS`
4. Add audit logging: `Auth::logAction($userId, 'ACTION_CODE', 'Details')`

## Styling

- Bootstrap 5.3.0 via CDN
- Custom CSS: `assets/css/style.css` — glassmorphism, CSS variables, premium buttons
- Primary color: `#4f46e5` (indigo)
- Font: Google Fonts "Outfit"
- Classes: `.card-glass`, `.btn-premium`, `.form-control-premium`, `.table-premium`

## JS Patterns

- No build step — vanilla JS, loaded via `<script>` tags
- AJAX uses `fetch()` returning JSON (`cardapio_action.php` is the pattern)
- Money mask: `parseBrlToCents()` / `formatBrl()` pattern on inputs
- Bootstrap modals for forms (not separate pages)

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
- Sidebar nav is copy-pasted in every page — if you add a menu item, update ALL page files
