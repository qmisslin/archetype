# Archetype

Archetype is a PHP-based headless CMS designed around a schema-driven HTTP API.
It exposes content, users, files, and system operations exclusively through endpoints, without any built-in UI.

The API is role-based (`ADMIN`, `EDITOR`, `PUBLIC`) and relies on token authentication.
All content is validated against versioned schemas and stored as structured data.

This repository contains:
- `specs.md`: the authoritative functional and technical specification
- `checklist.md`: the active development roadmap
- The core framework implementation
- Internal developer tools for testing and debugging APIs

Archetype is designed to be:
- framework-agnostic
- easy to self-host
- suitable for custom admin panels and frontends
- production-oriented with explicit configuration and validation

---

## Documentation

The project documentation is intentionally centralized and kept up to date.

- [`specs.md`](./specs.md)  
  Complete architecture and API reference:
  - Overall system design
  - File organization
  - Environment configuration
  - Database schema and relations
  - Authentication, roles, permissions
  - API routes (inputs, outputs, access rules)
  - Schema validation
  - Entries lifecycle and search
  - Uploads, logs, trackers, email services

- [`checklist.md`](./checklist.md)  
  Living development roadmap, updated continuously as features are implemented.

---

## Requirements

- PHP 8.0 or newer
- Composer
- Git
- A web server (Apache recommended)

---

## Installation

### Clone the repository

```bash
git clone <repository-url>
cd archetype
````

### Install dependencies

```bash
composer install
```

### Environment configuration

Create your environment file:

```bash
cp .env.example .env
```

Edit `.env` and configure:

* `APP_URL` (public base URL, used for emails and links)
* Database settings
* SMTP settings

Example for a local SQLite setup:

```ini
APP_URL=http://127.0.0.1:8080

DB_TYPE=SQLITE
DB_FILEPATH=data/database.sql

SMTP_USER=you@example.com
SMTP_PASS=your_password
SMTP_HOST=smtp.example.com
SMTP_PORT=465
SMTP_SECURE=ssl
```

---

## Local Development Server (Apache / Homebrew)

Archetype is designed to run behind a real web server.
For local development on macOS, Apache via Homebrew provides a setup close to production behavior.

### Install Apache

```bash
brew install httpd
```

### Start / Restart Apache

Apache can be managed as a background service:

```bash
brew services start httpd
brew services restart httpd
brew services stop httpd
```

By default, Homebrew Apache listens on port `8080`.

Verify it is running by opening:

```
http://127.0.0.1:8080
```

---

### Apache Configuration

Edit the main Apache configuration file:

```bash
nano /opt/homebrew/etc/httpd/httpd.conf
```

Ensure required modules are enabled:

```apache
LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so
```

Enable `.htaccess` support:

```apache
<Directory "/opt/homebrew/var/www">
    AllowOverride All
    Require all granted
</Directory>
```

Restart Apache after changes:

```bash
brew services restart httpd
```

---

### Virtual Host Example

Configure a virtual host pointing to the Archetype project directory:

```apache
<VirtualHost *:8080>
    ServerName archetype.local
    DocumentRoot "/path/to/archetype"

    <Directory "/path/to/archetype">
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "/opt/homebrew/var/log/httpd/archetype-error.log"
    CustomLog "/opt/homebrew/var/log/httpd/archetype-access.log" combined
</VirtualHost>
```

Restart Apache once configured:

```bash
brew services restart httpd
```

Update your `.env` accordingly:

```ini
APP_URL=http://archetype.local:8080
```

If no virtual host is used:

```ini
APP_URL=http://127.0.0.1:8080
```

---

## Development Tools

The repository includes internal HTML-based developer tools to test and inspect:

* Logs
* Email sending
* API endpoints
* Internal state

These tools are **not intended for production exposure** and must only be enabled in controlled environments.

---

## Project Status

Active development.

Core infrastructure is in place. Current focus is on authentication, user lifecycle, and security-related features.

High-level status:

* [x] Core bootstrap and routing
* [x] Environment validation
* [x] Database layer (SQLite / MySQL)
* [x] Logging system and API
* [x] Email service
* [ ] User authentication flows
* [ ] Password reset and security hardening
* [ ] Content entries and schemas
* [ ] Permissions and access control refinement

See [`checklist.md`](./checklist.md) for the detailed and up-to-date roadmap.

---

## License

MIT License.

You are free to use, modify, and distribute this project in both open-source and commercial contexts.

See [`LICENSE.md`](./LICENSE.md) for details.