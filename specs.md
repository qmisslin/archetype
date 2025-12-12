# Archetype: A PHP Headless CMS solution

The API is headless: CORE management files and routes provide a pure API-based management layer (no graphical interface).
The data editor or the website consuming these data and using these endpoints are created independently and form the “front”.
There are three default roles in the API:
- **ADMIN**: role that allows creating schemas and users.
- **EDITOR**: role that allows creating data based on schemas.
- **PUBLIC**: role that allows accessing data without restrictions.

---

# File organization

.
├── .htaccess
├── index.php
├── admin.php
├── api/
│   ├── logs/
│   ├── trackers/
│   ├── email/
│   ├── attempts/
│   ├── users/
│   ├── uploads/
│   ├── schemes/
│   └── entries/
├── core/
│   ├── Env.php
│   ├── Database.php
│   ├── Logs.php
│   ├── Trackers.php
│   ├── Email.php
│   ├── Attempts.php
│   ├── Users.php
│   ├── Uploads.php
│   ├── Schemes.php
│   └── Entries.php
└── data/
    ├── database.sql
    ├── uploads/
    └── logs/

Below is the expected file organization on the server.

At the server root, you can find all PHP view files:
- `./`: PHP code for public pages and other pages are at the root
- `./index.php`: PHP code used to display the public front (API calls to retrieve resources)
- `./admin.php`: PHP code used to display the administration interface front (CORE API calls to retrieve resources)

In the `./core` directory, you can find all code related to server management:
- `./core/`: API classes and their methods (accessible directly from PHP)

In the `./api` directory, you can find all PHP API routes:
- `./api/`: routes allowing access to CORE classes and methods through HTTP(S) requests

In the `./data` directory, you can find the database and all uploaded files stored on the server:
- `./data/database.sql`: the SQLite database
- `./data/uploads/`: all files uploaded to the server
- `./data/logs`: all server log files

The `.htaccess` at the server root must have the following specifications:
- protected access to `./data` (only accessible from PHP scripts)
- protected access to `./core` (only accessible from PHP scripts)
- public access to the rest (with access restrictions depending on pages, handled by PHP code)

---

# Database tables

The database stores several tables:

## TOKENS
- `token` (primary key string): random unique string to authorize the user
- `expiration timestamp` (int): used to limit the current token validity
- `userId` (int): owner of the token
- `role` (enum): same as owner of the token
- `creation timestamp`
- `ip`: IP of the user who created the token

## ATTEMPTS
- `ip` (string primary key)
- `user email` (string)
- `attempt number` (int): number of attempts
- `type` (enum `[LOGIN, RESET_PASSWORD]`): type of request
- `timestamp`: last try attempt time

## SCHEMES
- `id` (primary key int)
- `version` (int): the version of the current scheme
- `name` (string): the display name of the scheme
- `fields` (json string): the current data scheme as specified
- `creation timestamp`
- `modification timestamp`
- `last modification userId`

## ENTRIES
- `id` (primary key int)
- `schemeId` (int): the scheme used to structure the data
- `schemeVersion` (int): the version used to structure the data
- `data` (json string): the data
- `creation timestamp`
- `modification timestamp`
- `last modification userId`

## UPLOADS
- `id` (primary key int)
- `name` (string)
- `filepath` (string, nullable): file path inside `/data/uploads/`. If null, the entry is considered as a folder.
- `access` (json): who can access this folder, example: `["PUBLIC","EDITOR","ADMIN"]`
- `mime` (string, nullable): file mime
- `extension` (string, nullable): file extension
- `parentId` (reference, nullable): parent file (= folder)
- `creation timestamp`
- `modification timestamp`
- `last modification userId`

## USERS
- `id` (primary key int)
- `email` (string): used for login
- `password` (hashed string): used for login
- `name` (string): shared name for other editors/admins
- `role` (enum `[PUBLIC, EDITOR, ADMIN]`): used to manage access
- `enable` (boolean): used to manually disable account
- `creation timestamp`

## LOGS
- `id`
- `logfile` (`date + time + milliseconds`.log): name of the log file
- `timestamp`: creation date of the log file (used to get the most recent file)
- `stats` (json): computed stats data (request count, visited pages, IP list, etc.)
- `modification date` (date): date of the last stats compute

## TRACKERS
- `id` (primary key): unique id used to track inside links
- `name` (string): tracker name
- `description` (string): describes the tracker usage. For example, in a mail campaign, trackers are used on links.
- `creation timestamp`
- `modification timestamp`
- `last modification userId`: id of the creator of this tracker

---

# Env class

Loads the environment file for the following values:

```ini
DB_TYPE=SQLITE
DB_HOST=
DB_PORT=
DB_NAME=
DB_USER=
DB_PASS=
EMAIL_USER=
EMAIL_PASS=
````

`Init()`
Loads environment variables.

---

# Database class

The Database class creates the database and the SQLite file if it does not already exist in `/data` (for DB_TYPE=SQLITE).
It creates all database tables if they do not exist, and provides access to the database.

`Init()`
Function that connects to the database and creates tables if they do not already exist.

---

# Logs class

Endpoints:

* `[PUBLIC]` **POST** `/api/logs/message` (type, content)
* `[ADMIN] [EDITOR]` **POST** `/api/logs/compute` (logId, logFileName)
* `[ADMIN] [EDITOR]` **GET** `/api/logs/range` ()
* `[ADMIN] [EDITOR]` **GET** `/api/logs/get` (startDate nullable, endDate nullable)
* `[ADMIN]` **GET** `/api/logs/download` (filename)
* `[ADMIN]` **DELETE** `/api/logs/remove` (filename)
* `[ADMIN]` **POST** `/api/logs/purge` ()

`range()`
Retrieves the maximum date and the minimum date of entries in the `LOGS` table.

`get(startDate nullable, endDate nullable)`
Retrieves the list of all entries (stats included) from the logs table.
Start date and end date are optional and can be used to limit the number of requested logs.
For each entry, the API checks whether the `/data/logs/` file exists and returns that result in the response.

`download(filename)`
Downloads a log file.

`remove(filename)`
Deletes a log file.

`purge()`
Deletes all statistics related to a log file that has been deleted.

`getCurrent()`
Retrieves the most recent entry from the `LOGS` table (based on creation date).
It checks the corresponding log file, and if it exceeds 3MB:

* compute current log file stats using `computeStats()` and store the result
* create a new entry in the database and create a new empty log file

`message(type, content)`
Adds a message to the current log file.

`compute(logId, logFileName)`
Reads a log file and computes statistics related to this file.
If stats were already computed, they are recomputed anyway (because temporary stats may exist).

Example logs:

```log
{"ts":"2025-01-15T09:12:03+01:00","type":"REQ","level":"info","ip":"203.0.113.10","method":"GET","path":"/","status":200,"duration_ms":45,"tid":null,"ua":"Mozilla/5.0 (Windows NT 10.0; Win64; x64)","referer":null,"bytes":4213}
{"ts":"2025-01-15T09:12:05+01:00","type":"REQ","level":"info","ip":"203.0.113.45","method":"GET","path":"/promo/hiver","status":200,"duration_ms":87,"tid":"NEWS_2025_JAN","ua":"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)","referer":"https://mail.example.com/open/123","bytes":5321}
{"ts":"2025-01-15T09:12:06+01:00","type":"INF","level":"info","ip":"203.0.113.45","message":"User viewed promo page","category":"business","path":"/promo/hiver","tid":"NEWS_2025_JAN"}
{"ts":"2025-01-15T09:13:11+01:00","type":"REQ","level":"info","ip":"198.51.100.23","method":"GET","path":"/produit/42","status":200,"duration_ms":132,"tid":"ADS_CAMPAIGN_42","ua":"Mozilla/5.0 (Linux; Android 12; Pixel 6)","referer":"https://ads.example.com/click/42","bytes":6789}
{"ts":"2025-01-15T09:13:12+01:00","type":"WRN","level":"warning","ip":"198.51.100.23","message":"Missing optional field 'coupon'","category":"business","path":"/produit/42","tid":"ADS_CAMPAIGN_42"}
{"ts":"2025-01-15T09:13:20+01:00","type":"REQ","level":"info","ip":"203.0.113.10","method":"POST","path":"/api/order","status":500,"duration_ms":953,"tid":null,"ua":"Mozilla/5.0 (Windows NT 10.0; Win64; x64)","referer":"https://www.example.com/panier","bytes":1024}
{"ts":"2025-01-15T09:13:20+01:00","type":"ERR","level":"error","ip":"203.0.113.10","message":"Order creation failed","category":"order","path":"/api/order","tid":null,"exception_class":"RuntimeException","exception_message":"Database connection lost","stack_hash":"3c9f2a7b"}
{"ts":"2025-01-15T09:14:02+01:00","type":"INF","level":"info","ip":"203.0.113.10","message":"Background job processed order retry","category":"job","path":"/jobs/order-retry","tid":null}
{"ts":"2025-01-15T09:14:05+01:00","type":"REQ","level":"info","ip":"203.0.113.10","method":"POST","path":"/api/order","status":201,"duration_ms":412,"tid":null,"ua":"Mozilla/5.0 (Windows NT 10.0; Win64; x64)","referer":"https://www.example.com/panier","bytes":2048}
```

Example of computed statistics data for a log file:

```json
{
  "meta": { "generated_at": "2025-01-15T10:00:00+01:00", "rows_processed": 9},
  "period": { "date": "2025-01-15", "from": "2025-01-15T09:12:03+01:00", "to": "2025-01-15T09:14:05+01:00"},
  "global": { "requests": 5, "unique_clients": 3, "errors": 1, "warnings": 1, "infos": 2, "total_bytes": 19395, "total_duration_ms": 1629, "avg_duration_ms": 325.8},
  "requests_by_status": { "200": 3, "201": 1, "500": 1},
  "requests_by_path": {
    "/": { "requests": 1, "avg_duration_ms": 45, "status_counts": { "200": 1 }, "bytes": 4213, "warnings": 0, "errors": 0},
    "/promo/hiver": { "requests": 1, "avg_duration_ms": 87, "status_counts": { "200": 1 }, "bytes": 5321, "warnings": 0, "errors": 0},
    "/produit/42": { "requests": 1, "avg_duration_ms": 132, "status_counts": { "200": 1 }, "bytes": 6789, "warnings": 1, "errors": 0},
    "/api/order": { "requests": 2, "avg_duration_ms": 682.5, "status_counts": { "500": 1, "201": 1 }, "bytes": 3072, "warnings": 0, "errors": 1}
  },
  "tid_stats": {
    "NEWS_2025_JAN": { "request_count": 1, "unique_ips": 1},
    "ADS_CAMPAIGN_42": { "request_count": 1, "unique_ips": 1}
  },
  "timeline_per_day": {
    "2025-01-15T09:12": { "requests": 2, "messages": 0, "errors": 0, "warnings": 0},
    "2025-01-16T09:13": { "requests": 2, "messages": 0, "errors": 1, "warnings": 1},
    "2025-01-17T09:14": { "requests": 1, "messages": 0, "errors": 0, "warnings": 0}
  }
}
```

---

# Trackers class

Endpoints:

* `[ADMIN] [EDITOR]` **GET** `/api/trackers/list` ()
* `[ADMIN] [EDITOR]` **POST** `/api/trackers/create` (name, description)
* `[ADMIN] [EDITOR]` **PATCH** `/api/trackers/edit` (trackerId, name, description)
* `[ADMIN] [EDITOR]` **DELETE** `/api/trackers/delete` (trackerId)

`List()`
Lists all trackers stored in the database.

`Create(name, description)`
Creates a tracker.

`Edit(trackerId, name, description)`
Updates a tracker name and/or description.

`Delete(trackerId)`
Deletes a tracker.

---

# Email class

Endpoints:

* `[EDITOR] [ADMIN]` **POST** `/api/email/send` (string dest, string obj, string content)

`Send(string[] dest, string obj, string type, string content)`
Sends an email. The `EMAIL_USER`/`EMAIL_PASS` account is used to send.
Recipients must not see each other: one email is sent to each recipient.
In principle, the recipient list could include mailing lists.
For `content`, it can be a full HTML document (defined by type parameter).

---

# Attempts class

Used to manage secured actions: login or password reset.
Attempts are identified by the user IP and the action type.
The system looks up the IP and the action type. A blocked IP remains blocked regardless of the action type, but it does not prevent the user from connecting from another IP.

Mechanism:

* attempt 1: add to database (remove old `LOGIN` or `RESET_PASSWORD` records for the same ip identifier)
* attempt 2: increment attempts, 0 minutes waiting
* attempt 3: increment attempts, 5 minutes waiting
* attempt 4: increment attempts, 10 minutes waiting
* attempt 5: increment attempts, 60 minutes waiting
* attempt 6: action blocked for this IP (requires an ADMIN action to unblock the IP)

Endpoints:

* `[ADMIN]` **DELETE** `/api/attemps/delete` (ip)
* `[ADMIN]` **GET** `/api/attemps/list` ()

`get(ip, type)`
Retrieves a value from the `ATTEMPS` table if it exists.

`set(ip, type, value)`
Creates/updates a value (overwrite possible on `ip + type`).

`delete(ip, type)`
Deletes attempts for an action and an IP.

`list()`
Retrieves the list of all attempts for all IPs.

---

# Users class (and related routes)

Endpoints:

* `[ADMIN] [EDITOR]` **POST** `/api/users/PruneTokens` ()
* `[PUBLIC]` **POST** `/api/users/CreateAdminUser` ()
* `[PUBLIC]` **POST** `/api/users/Login` (string mail, string password) // ATTEMPTS
* `[PUBLIC]` **POST** `/api/users/Logout` (string email, token)
* `[PUBLIC]` **POST** `/api/users/LogoutEverywhere` (string email, token)
* `[ADMIN]` **POST** `/api/users/CreateUser` (mail, name, role)
* `[PUBLIC]` **POST** `/api/users/ForgotPassword` (mail) // ATTEMPTS
* `[PUBLIC]` **POST** `/api/users/ChangePassword` (token, mail, password)
* `[ADMIN]` **PATCH** `/api/users/ToggleUser` (userId, value)
* `[OWNER] [ADMIN]` **DELETE** `/api/users/DeleteUser` (userId)
* `[OWNER] [ADMIN]` **PATCH** `/api/users/Edit` (userId, name, email)
* `[ADMIN]` **PATCH** `/api/users/SetRole` (userId, role)
* `[ADMIN] [EDITOR]` **GET** `/api/users/GetUserList` ()

`PruneTokens()`
Deletes all expired tokens from the database (or tokens whose users no longer exist).

`CreateAdminUser()`
Creates an ADMIN user with the email specified in the environment variable `EMAIL_USER` if it does not already exist (with a random password).
Whether the user already exists or not, a password reset token is sent by email to `EMAIL_USER`.

`Login(string mail, string password)`
Checks the number of attempts in the database: if a waiting period is active, returns an error without even testing credentials.
If the login succeeds (email and password match), creates a random token stored in the `TOKENS` table.
This token is returned to the user to authorize requests.
A successful login removes all `LOGIN` entries for the user IP in the `ATTEMPTS` table.
Success return: login token
Error return: invalid email/password or waiting period not finished

`Logout(string email, token)`
Deletes the user login token.
This action is only possible if the token is valid.

`LogoutEverywhere(string email, token)`
Deletes all login tokens for a user.
This action is only possible if the token is valid.

`CreateUser(mail, name, role)`
Allows an ADMIN user to create a new account.
If the user is created, a password reset email is sent to the new user.

`SetUserInfos(userId, name, email)`
Edits a user info.

`ForgotPassword(mail)`
Sends a password reset token (valid 1 hour) by email to a user if the email exists in the database.
This token is stored in the database. Any other reset password token is deleted for that user.
Same mechanism as login: you can send two reset tokens before increasing delays between new attempts.
The link sent by email points to the endpoint `/api/users/changePassword`.

`ChangePassword(token, mail, password)`
Changes the password for a user if the token is valid.
The token stores the source IP, which is used to remove all `RESET_PASSWORD` entries for that IP from the `ATTEMPS` table.
The token is deleted after usage.

`ToggleUser(userId, value)`
Enables/disables a user.

`DeleteUser(userId)`
Deletes a user (by an administrator).

`SetRole(userId, role)`
Sets a role for a user.

`GetUserList()`
Returns the list of users (id, name, email, enabled or not, role, creation date).

---

# Uploads class

Endpoints:

* `[ADMIN] [EDITOR]` **POST** `/api/uploads/upload` (file, name, folderId (nullable), access (PUBLIC, EDITOR, ADMIN))
* `[ADMIN] [EDITOR]` **POST** `/api/uploads/create` (name, folderId (nullable), access (PUBLIC, EDITOR, ADMIN))
* `[ADMIN] [EDITOR]` **POST** `/api/uploads/replace` (fileId, file)
* `[ADMIN] [EDITOR]` **DELETE** `/api/uploads/remove` (fileId)
* `[ADMIN] [EDITOR]` **PATCH** `/api/uploads/edit` (fileId, name, access)
* `[ADMIN] [EDITOR]` **GET** `/api/uploads/list` (folderId) folderId can be null for root folder
* `[PUBLIC]` **GET** `/api/uploads/get` (fileId).

`upload(file, name, folderId(nullable), access [PUBLIC, EDITOR, ADMIN, ...])`
Uploads the attached file into `/upload` and creates an entry through `Create()`.

`create(name, parentFileId(nullable), access [PUBLIC, EDITOR, ADMIN, ...])`
Creates a file or folder in the `UPLOADS` table.
`upload` calls `create`. But if `create` is called alone, no file is provided and the entry is considered as a folder.

`replace(file, fileId)`
Uploads and replaces an existing file.

`remove(fileId)`
Delete a file.

`edit(fileId)`
Modify the data and access for a file

`list(fileId)`
Get the list of children files for current fileId

`get(fileId)`
Check file access and serve file as is (to use it with <img src=""> for example)

---

# Schemes routes

Routes:

* `[ADMIN]` **POST** `/api/schemes/create` (name)
* `[ADMIN]` **DELETE** `/api/schemes/remove` (schemeID)
* `[ADMIN]` **PATCH** `/api/schemes/rename` (schemeID, name)
* `[ADMIN]` **POST** `/api/schemes/addField` (schemeID, key, label, required, type, is-array, default, access, JSON rules)
* `[ADMIN]` **GET** `/api/schemes/list` ()
* `[ADMIN]` **GET** `/api/schemes/get` (schemeID)
* `[ADMIN]` **DELETE** `/api/schemes/removeField` (schemeID, key)
* `[ADMIN]` **PATCH** `/api/schemes/rekeyField` (schemeID, key, newKey)
* `[ADMIN]` **PATCH** `/api/schemes/updateField` (schemeID, key, newField)
* `[ADMIN]` **PATCH** `/api/schemes/indexField` (schemeID, key, index)

`create(name)`
Creates a new (empty) schema.

`remove(schemeID)`
Deletes a schema by its id.
All data related to this schema will be deleted.

`rename(schemeID, name)`
Changes the schema name.
The name does not change data because entries reference schemas by ID.

`addField(schemeID, key, label, required, type, is-array, default, access, JSON rules)`
Adds a field to the structure.

`list()`
Retrieves the list of all defined schemas (id, name).

`get(schemeID)`
Retrieves the full content of a schema.

`removeField(schemeID, key)`
Removes a field from the structure.
All database entries are updated by removing the field.

`rekeyField(schemeID, key, newKey)`
Replaces a schema key name. This key is updated across all data.
A key can be set only if it does not already exist in the structure.

`updateField(schemeID, key, newField)`
Updates fields in a schema structure.
If the following fields are modified:
`required`, `type`, `is-array`, `rules`
then the schema version is incremented.

`indexField(schemeID, key, index)`
Changes a field index in the form (does not change data).

---

# Entries routes

Routes:

* `[ADMIN] [EDITOR]` **POST** `/api/entries/Create` (schemeId, data)
* `[ADMIN] [EDITOR]` **PATCH** `/api/entries/Edit` (entryId, data)
* `[ADMIN] [EDITOR]` **DELETE** `/api/entries/Remove` (entryId)
* `[ADMIN] [EDITOR]` **POST** `/api/entries/Duplicate` (entryId)
* `[PUBLIC]` **GET** `/api/entries/List` (schemeId, bool outdated)
* `[PUBLIC]` **GET** `/api/entries/GetById` (entryId)
* `[PUBLIC]` **POST** `/api/entries/Search` (scheme, bool outdated, json search)

`Create(schemeId, data)`
Adds an entry that conforms to the current schema.

`Edit(entryId, data)`
Updates an entry.
This update is only possible if it conforms to the current schema.

`Remove(entryId)`
Deletes an entry.

`Duplicate(entryId)`
Duplicates an entry data.
It is possible to duplicate outdated data.

`List(scheme, bool outdated)`
Lists entries for a given schema.
The `outdated` field specifies whether to also retrieve entries that are no longer up to date with the schema.
For a schema, the retrieved data are fully read by PHP and then sorted:
depending on the user token, data are filtered to remove values the user cannot access.

`GetById(entryId)`
Retrieves values (filtered by access rules) of an entry.

`Search(scheme, bool outdated, json search)`
Retrieves the list of entries matching the JSON filter.
The JSON uses the following AST format: a hierarchical array where each level is `[METHOD, ...PARAMS]`

Example:

```json
["AND",
  ["CONTAINS",["KEY","article-name"],["VALUE","new"]],
  ["GREATER-THAN",["KEY","price"],["VALUE",10]],
  ["OR",
    ["EQUAL",["KEY","status"],["VALUE","published"]],
    ["AND",
      ["EXISTS",["KEY","promo"]],
      ["LESS-OR-EQUAL",["KEY","promo"],["VALUE",50]]
    ]
  ]
]
```

Whitelist of supported conditional methods:

* `AND`: combines multiple expressions, all must be true.
* `OR`: combines multiple expressions, at least one must be true.
* `NOT`: inverts the result of the child expression.
* `KEY`: accesses a top-level key in the entry JSON.
* `PATH`: accesses a nested key through an explicit path.
* `EQUAL`: strict equality between a value and the target field.
* `NOT-EQUAL`: field value is different from the given value.
* `GREATER-THAN`: field is strictly greater than the given value.
* `GREATER-OR-EQUAL`: field is greater than or equal to the given value.
* `LESS-THAN`: field is strictly less than the given value.
* `LESS-OR-EQUAL`: field is less than or equal to the given value.
* `IN`: field belongs to a list of allowed values.
* `NOT-IN`: field does not belong to a list of forbidden values.
* `BETWEEN`: field is between two inclusive bounds.
* `CONTAINS`: field contains the given substring.
* `STARTS-WITH`: field starts with the given substring.
* `ENDS-WITH`: field ends with the given substring.
* `ANY`: at least one array element satisfies the condition.
* `ALL`: all array elements satisfy the condition.
* `EXISTS`: field is present in the entry JSON.
* `IS-NULL`: field exists and is null.
* `IS-NOT-NULL`: field exists and is not null.

---

# Schemes description

Schemes define a data structure for values stored in the `ENTRIES` table.

## Field definition (properties)

### Required properties (for all field types)
- `key` (string)  
  Field identifier stored in entry JSON (e.g. `"article-name"`).

- `label` (string)  
  Human-readable label (UI-oriented).

- `required` (boolean)  
  Whether the value must be present.

- `default` (any)  
  Default value used when creating an entry (required for each field).

- `type` (enum)  
  Supported values: `STRING | BOOLEAN | NUMBER | INTEGER | FLOAT | ENTRIES | UPLOADS`.

- `is-array` (boolean)  
  Whether the stored value is an array (`true`) or a scalar (`false`).

- `access` (string[])  
  Who can access this value, e.g. `["admin","editor","public"]`.

### `rules` (optional)
Custom rules for validation (depend on each type validation).  
If a key is not present, the rule is considered as not defined.

#### Entry reference rules
- `scheme` (int)  
  Only for `type == ENTRIES`: target scheme id referenced by this field.

#### Format (optional)
- `format` (string)  
  Used to specify the semantic format of a field value.

Supported examples:
- `duration`: duration in milliseconds (INT)
- `timestamp`: Unix timestamp with milliseconds (INT)
- `price`: monetary value (string formatted like `"value€"`)
- `percentage`: number between 0 and 100
- `rating`: bounded number between 0 and 5
- `geo-point`: latitude/longitude (string formatted like `"lat:long"`)
- `address`: structured address (string)
- `markdown`: markdown content (string)
- `html`: raw HTML (string)
- `json`: free JSON content (string)
- `hex-color`: hexadecimal color (string)

#### General rules
- `enum` (array)  
  Allowed values list (enum values).

#### String rules
- `min-char` (int)
- `max-char` (int)
- `pattern` (string)  
  Regular expression.

#### Number rules
- `min-value` (number)
- `max-value` (number)
- `step` (number)  
  Rounding step for floats.

#### Array rules
- `min-length` (int)
- `max-length` (int)

#### Timestamp rules
- `min` (int)  
  Minimum date.
- `max` (int|string)  
  Maximum date.
- `future-only` (boolean)  
  Must be in the future only.
- `past-only` (boolean)  
  Must be in the past only.

#### Coordinates rules
- `min-lat` (number)
- `max-lat` (number)
- `min-lng` (number)
- `max-lng` (number)

#### Upload reference rules
- `mimetypes` (string[])  
  Allowed mime types (e.g. images: `["image/jpeg","image/png"]`, videos: `["video/mp4"]`).
- `max-size` (int)  
  Maximum size in octets.

## Example scheme

```json
[
  {
    "key": "article-name",
    "label": "Nom de l'article",
    "required": true,
    "default": "Mon super article",
    "type": "STRING",
    "is-array": false,
    "access": ["admin","editor","public"],
    "rules": {
      "scheme": "schemeId",
      "format": "timestamp",
      "enum": [],
      "min-char": 1,
      "max-char": 200,
      "pattern": "",
      "min-value": 0,
      "max-value": 200,
      "step": 0.01,
      "min-length": 1,
      "max-length": 200,
      "min": 0,
      "max": "",
      "future-only": false,
      "past-only": false,
      "min-lat": 0,
      "max-lat": 0,
      "min-lng": 0,
      "max-lng": 0,
      "mimetypes": ["image/jpeg","image/png"],
      "max-size": 256
    }
  }
]
````

Then in the entry table you would have:

```json
{
  "article-name": "Article principal de mon blog"
}
```

