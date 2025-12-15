## Development checklist

### 0) Project bootstrap
- [x] Create the directory structure (`api/`, `core/`, `data/`, etc.)
- [x] Add `.htaccess`: deny access to `/data` and `/core`, public access to the rest (handled by PHP)
- [x] Add `.env` + `.gitignore` (e.g. ignore `/data/*` except placeholders)

### 1) Minimal runtime core
- [x] `core/Env.php`: load and validate environment variables (`Init()`)
- [x] JSON API helper (responses, errors, HTTP status codes)
- [x] Minimal router for `api/*` (dispatch, HTTP method, JSON/multipart body)

### 2) Database + Logs first
- [ ] `core/Database.php`: SQLite connection + create `/data/database.sql` if missing
- [ ] `core/Database.php`: create minimal tables **LOGS** (+ optionally USERS/TOKENS if needed)
- [ ] Create `/data/logs/` + permissions
- [ ] `core/Logs.php`: `getCurrent()` + create LOGS entry + create empty `.log` file
- [ ] `core/Logs.php`: `message(type, content)` (append JSONL)
- [ ] Automatic logging middleware on **all** `/api/*` routes:
  - [ ] write a `REQ` line (method/path/status/duration/ip/ua/referer/bytes/tid)
  - [ ] expose helpers for `INF/WRN/ERR` (category, message, stack_hash on exception)
- [ ] Log rotation handling (if > 3MB): compute stats + new DB entry + new file
- [ ] Logs routes (for early debugging):
  - [ ] `[PUBLIC]` `POST /api/logs/message`
  - [ ] `[ADMIN][EDITOR]` `GET /api/logs/get` (+ date filters)
  - [ ] `[ADMIN][EDITOR]` `GET /api/logs/range`
  - [ ] `[ADMIN][EDITOR]` `POST /api/logs/compute`
  - [ ] `[ADMIN]` `GET /api/logs/download`, `DELETE /api/logs/remove`, `POST /api/logs/purge`

### 3) Minimal auth (to secure non-PUBLIC endpoints)
- [ ] Tables **USERS**, **TOKENS**, **ATTEMPTS** (if not already created)
- [ ] Auth helper: token extraction, expiration check, user/role resolution, route guards
- [ ] `core/Attempts.php`: `get/set/delete/list` + delay/blocking logic
- [ ] `core/Users.php`: `CreateAdminUser`, `Login`, `Logout`, `PruneTokens` (minimum viable)
- [ ] Systematic logging of auth errors (WRN) without leaking sensitive information

### 4) Email (reset + admin bootstrap)
- [ ] `core/Email.php`: `Send(dest[], obj, type, content)`
- [ ] Route `/api/email/send` (ADMIN/EDITOR)
- [ ] `ForgotPassword` + `ChangePassword` (1h reset tokens) + related logs

### 5) Schemes
- [ ] `core/Schemes.php`: `create/remove/rename/list/get`
- [ ] `core/Schemes.php`: `addField/removeField/rekeyField/updateField/indexField`
- [ ] Version handling + entry migrations (`removeField` / `rekeyField`)
- [ ] Logs: every schema mutation -> `INF` (schemeId, version, userId)

### 6) Entries
- [ ] `core/Entries.php`: `Create/Edit/Remove/Duplicate`
- [ ] Data validation against schema (types, required, is-array, rules)
- [ ] `List/GetById` with `access` filtering based on token/role
- [ ] `Search` AST (whitelist) + logging of slow/invalid queries

### 7) Uploads
- [ ] `core/Uploads.php`: `create/upload/replace/remove/edit/list/get`
- [ ] Storage in `/data/uploads/` + mime/extension/size checks + anti-traversal
- [ ] Access control via `access[]`
- [ ] Logs: upload/replace/remove + anomalies (unexpected mime, size, access)

### 8) Trackers
- [ ] `core/Trackers.php`: `List/Create/Edit/Delete`
- [ ] Routes `/api/trackers/*` (ADMIN/EDITOR)

### 9) Logs statistics (hardening)
- [ ] `compute(logId, logFileName)`: recompute stats (global/status/path/tid/timeline)
- [ ] `purge()`: clean stats entries without a file
- [ ] Tests: tolerant JSONL parsing (invalid lines), performance on large files

### 10) Fronts (bootstrap)
- [ ] `index.php`: example API consumption (PUBLIC)
- [ ] `admin.php`: admin bootstrap (login + API calls)
- [ ] No UI rendering in `core/` or `api/`

### 11) Quality & tests
- [ ] Unit tests: schema/entry validation, AST, attempts, tokens, log parsing/stats
- [ ] Integration tests: routes + roles + automatic logging on each request
- [ ] Documentation: auth, routes, JSONL log format, stats, rotation
