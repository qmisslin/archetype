## Checklist de développement

### 0) Bootstrap projet
- [ ] Créer l’arborescence (`api/`, `core/`, `data/`, etc.)
- [ ] Ajouter `.htaccess` : deny sur `/data` et `/core`, accès public au reste (contrôlé par PHP)
- [ ] Ajouter `.env` + `.gitignore` (ex: ignorer `/data/*` hors placeholders)

### 1) Noyau runtime minimal
- [ ] `core/Env.php` : lecture + validation des variables (`Init()`)
- [ ] Helper JSON API (réponses, erreurs, codes HTTP)
- [ ] Router minimal pour `api/*` (dispatch + méthode HTTP + body JSON/multipart)

### 2) Base + Logs en premier
- [ ] `core/Database.php` : connexion SQLite + création `/data/database.sql` si absent
- [ ] `core/Database.php` : création tables minimales **LOGS** (+ éventuellement USERS/TOKENS si besoin)
- [ ] Créer `/data/logs/` + permissions
- [ ] `core/Logs.php` : `getCurrent()` + création entrée LOGS + création fichier `.log` vide
- [ ] `core/Logs.php` : `message(type, content)` (append JSONL)
- [ ] Middleware de log automatique sur **toutes** les routes `/api/*` :
  - [ ] écrire une ligne `REQ` (method/path/status/duration/ip/ua/referer/bytes/tid)
  - [ ] exposer un helper pour `INF/WRN/ERR` (catégorie, message, stack_hash si exception)
- [ ] Gestion rotation (si > 3MB) : compute stats + nouvelle entrée + nouveau fichier
- [ ] Routes Logs (pour debug très tôt) :
  - [ ] `[PUBLIC]` `POST /api/logs/message`
  - [ ] `[ADMIN][EDITOR]` `GET /api/logs/get` (+ filtres dates)
  - [ ] `[ADMIN][EDITOR]` `GET /api/logs/range`
  - [ ] `[ADMIN][EDITOR]` `POST /api/logs/compute`
  - [ ] `[ADMIN]` `GET /api/logs/download`, `DELETE /api/logs/remove`, `POST /api/logs/purge`

### 3) Auth minimale (pour sécuriser les endpoints non-PUBLIC)
- [ ] Tables **USERS**, **TOKENS**, **ATTEMPTS** (si pas déjà fait)
- [ ] Helper auth : lecture token, check expiration, résolution user/role, guard par route
- [ ] `core/Attempts.php` : `get/set/delete/list` + mécanique délais/blocage
- [ ] `core/Users.php` : `CreateAdminUser`, `Login`, `Logout`, `PruneTokens` (minimum viable)
- [ ] Log systématique des erreurs auth (WRN) sans fuite d’info sensible

### 4) Email (reset + admin bootstrap)
- [ ] `core/Email.php` : `Send(dest[], obj, type, content)`
- [ ] Route `/api/email/send` (ADMIN/EDITOR)
- [ ] `ForgotPassword` + `ChangePassword` (tokens reset 1h) + logs associés

### 5) Schemes
- [ ] `core/Schemes.php` : `create/remove/rename/list/get`
- [ ] `core/Schemes.php` : `addField/removeField/rekeyField/updateField/indexField`
- [ ] Gestion `version` + migrations des entries (removeField/rekeyField)
- [ ] Logs : toute mutation schema -> `INF` (schemeId, version, userId)

### 6) Entries
- [ ] `core/Entries.php` : `Create/Edit/Remove/Duplicate`
- [ ] Validation data vs schéma (types, required, is-array, rules)
- [ ] `List/GetById` avec filtrage `access` selon token/role
- [ ] `Search` AST (whitelist) + logs des requêtes lentes/invalides

### 7) Uploads
- [ ] `core/Uploads.php` : `create/upload/replace/remove/edit/list/get`
- [ ] Stockage `/data/uploads/` + contrôles mime/extension/taille + anti-traversal
- [ ] Contrôle d’accès `access[]`
- [ ] Logs : upload/replace/remove + anomalies (mime inattendu, taille, accès)

### 8) Trackers
- [ ] `core/Trackers.php` : `List/Create/Edit/Delete`
- [ ] Routes `/api/trackers/*` (ADMIN/EDITOR)

### 9) Logs stats (durcissement)
- [ ] `compute(logId, logFileName)` : recompute stats (global/status/path/tid/timeline)
- [ ] `purge()` : nettoyer stats d’entrées sans fichier
- [ ] Tests : parsing JSONL tolérant (lignes invalides), perf sur gros fichiers

### 10) Fronts (bootstrap)
- [ ] `index.php` : exemple consommation API (PUBLIC)
- [ ] `admin.php` : bootstrap admin (login + appels API)
- [ ] Aucun rendu UI dans `core/` et `api/`

### 11) Qualité & tests
- [ ] Tests unitaires : validation schema/entry, AST, attempts, tokens, logs parsing/stats
- [ ] Tests intégration : routes + rôles + logs auto sur chaque requête
- [ ] Docs : auth, routes, format logs JSONL, stats, rotation
