## Development checklist

### 0) Project bootstrap
- [x] Create the directory structure (`api/`, `core/`, `data/`, etc.)
- [x] Add `.htaccess`: deny access to `/data` and `/core`, rewrite rules for API
- [x] Add `.env` + `.gitignore`

### 1) Minimal runtime core
- [x] `core/Env.php`: load and validate environment variables (`Init()`)
- [x] `core/APIHelper.php`: standardized JSON responses and error handling
- [x] `core/Boot.php` & `core/Router.php`: Bootstrap architecture + Request helper
- [x] `router_dev.php`: Routing script for local development

### 2) Database & Logs System
- [x] `core/Database.php`: SQLite/MySQL connection + Auto-create tables
- [x] `core/Logs.php`: Rotation logic, JSONL writing, and Stats computation (`compute`)
- [x] Global Logging Middleware (in `Boot.php`)
- [x] **Logs API Routes** (Implementation complete, Security pending):
  - [x] `POST /api/logs/message` (Public)
  - [x] `GET /api/logs/get` & `range` (Admin/Editor)
  - [x] `POST /api/logs/compute` (Admin/Editor)
  - [x] `GET /api/logs/download` (Admin)
  - [x] `DELETE /api/logs/remove` & `POST /api/logs/purge` (Admin)

### 3) Email (reset + admin bootstrap)
- [x] `core/Email.php`: `Send(dest[], obj, type, content)`
- [x] Route `/api/email/send` (ADMIN/EDITOR)
- [x] `ForgotPassword` + `ChangePassword` (1h reset tokens) + related logs

### 4) Authentication & Security (Current Focus)
- [x] **Database**: Verify tables `USERS`, `TOKENS`, `ATTEMPTS` creation
- [x] `core/Attempts.php`: Brute-force protection (get/set/delete/list)
- [x] `core/Users.php`: User management logic (Create, Login, Logout, PruneTokens)
- [x] **Auth Middleware/Helper**:
  - [x] Token extraction & validation
  - [x] Role resolution (`ADMIN`, `EDITOR`, `PUBLIC`)
- [x] **Secure Endpoints**:
  - [x] Update `api/logs/*` to replace `TODO` with actual `Auth::check(['ADMIN'])`
  - [x] Ensure systematic logging of auth failures
  - [ ] Create Context class
  - [ ] Create unit test for each routes

### 5) Schemes
- [ ] `core/Schemes.php`: CRUD operations on JSON schemas
- [ ] `core/Schemes.php`: Field management (add/remove/rekey/update)
- [ ] Version handling + entry migrations
- [ ] Logs: every schema mutation -> `INF` (schemeId, version, userId)

### 6) Entries
- [ ] `core/Entries.php`: CRUD operations for content
- [ ] Data validation engine (validate input against Scheme rules)
- [ ] Access control: filter fields based on user role
- [ ] `Search` AST: Implement JSON-based search syntax (whitelist)

### 7) Uploads
- [ ] `core/Uploads.php`: File management & Virtual folders
- [ ] Storage security: mime/extension checks + path traversal prevention
- [ ] Access control: serve files based on `access[]` rules
- [ ] API Routes: upload, replace, list, get

### 8) Trackers
- [ ] `core/Trackers.php`: CRUD operations
- [ ] API Routes: `/api/trackers/*`

### 9) Frontends (Bootstrap)
- [x] `index.php` & `logs.html`: Developer Dashboard (Logs UI)
- [ ] `admin.php`: Main Admin Panel (Login + Schema/Content management)

### 10) Quality & Tests
- [ ] Unit tests: schema validation, AST logic, log parsing
- [ ] Integration tests: full route testing with Auth
- [ ] Documentation: Update `specs.md` with final implementation details