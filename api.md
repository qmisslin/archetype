# Archetype API Endpoints

### Auth & Users
* `POST /api/user-create-admin-user` `()` — First admin initialization (bootstrap).
* `POST /api/user-login` `(email, password)` — User login and token retrieval.
* `POST /api/user-forgot-password` `(email)` — Request password reset email.
* `POST /api/user-change-password` `(token, email, password)` — Password change via token.

### Emails
* `POST /api/email-send` `(dest, obj, content, type?)` — Send a transactional email.

### Logs
* `POST /api/logs-message` `(type?, ...)` — Manually add a log entry.
* `GET /api/logs-get` `(start?, end?)` — List log files with date filtering.
* `GET /api/logs-range` `()` — Get min/max log timestamps.
* `POST /api/logs-compute` `(id, filename)` — Force stats computation for a file.
* `GET /api/logs-download` `(filename)` — Download raw log file.
* `DELETE /api/logs-remove` `(filename)` — Delete a log file and its stats.
* `POST /api/logs-purge` `()` — Clean up orphaned DB entries.

### Schemes (Structure)
* `GET /api/schemes-list` `()` — List all existing schemes.
* `GET /api/schemes-get` `(schemeID)` — Retrieve full scheme definition.
* `POST /api/schemes-create` `(name)` — Create an empty scheme.
* `PATCH /api/schemes-rename` `(schemeID, name)` — Rename a scheme.
* `DELETE /api/schemes-remove` `(schemeID)` — Delete a scheme and all its data.
* `POST /api/schemes-add-field` `(schemeID, field)` — Add a field to the structure.
* `DELETE /api/schemes-remove-field` `(schemeID, key)` — Remove a field from the structure.
* `PATCH /api/schemes-update-field` `(schemeID, key, field)` — Update field properties.
* `PATCH /api/schemes-rekey-field` `(schemeID, oldKey, newKey)` — Rename a field key (migration).
* `PATCH /api/schemes-index-field` `(schemeID, key, index)` — Reorder a field in the list.

### Entries (Content)
* `GET /api/entries-list` `(schemeId, outdated?)` — List entries of a scheme.
* `GET /api/entries-get-by-id` `(entryId)` — Retrieve a single entry.
* `POST /api/entries-search` `(schemeId, search, outdated?)` — Complex search via AST JSON.
* `POST /api/entries-create` `(schemeId, data)` — Create a new entry.
* `PATCH /api/entries-edit` `(entryId, data)` — Edit an existing entry.
* `POST /api/entries-duplicate` `(entryId)` — Duplicate an entry.
* `DELETE /api/entries-remove` `(entryId)` — Permanently delete an entry.

### Uploads (Media)
* `GET /api/uploads-list` `(folderId?)` — List folder content (root if null).
* `POST /api/uploads-create` `(name, folderId?, access?)` — Create a virtual folder.
* `POST /api/uploads-store` `(file, name, folderId?, access?)` — Upload and store a physical file.
* `POST /api/uploads-replace` `(fileId, file)` — Replace the physical file of an entry.
* `PATCH /api/uploads-edit` `(fileId, name, access?)` — Edit metadata (name, access).
* `DELETE /api/uploads-remove` `(fileId)` — Delete a file or folder (recursive).
* `GET /api/uploads-get` `(fileId)` — Serve binary file or folder info.

### Trackers
* `GET /api/trackers-list` `()` — List all trackers.
* `POST /api/trackers-create` `(name, description?)` — Create a new tracker.
* `PATCH /api/trackers-edit` `(trackerId, name, description?)` — Edit a tracker.
* `DELETE /api/trackers-delete` `(trackerId)` — Delete a tracker.