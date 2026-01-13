# Archetype

Archetype is a PHP-based headless CMS designed around a schema-driven HTTP API. 
It exposes content, users, files, and system operations exclusively through endpoints, without any built-in UI.

The API is role-based (`ADMIN`, `EDITOR`, `PUBLIC`) and relies on token authentication.
All content is validated against versioned schemas and stored as structured data.

This repository contains:
- `specs.md`: the authoritative functional and technical specification
- `checklist.md`: the active development roadmap
- The core framework implementation
- `admin.php`: Internal system diagnostic and dashboard tool

Archetype is designed to be:
- framework-agnostic
- easy to self-host
- suitable for custom admin panels and frontends
- production-oriented with explicit configuration and validation

---

## Documentation

The project documentation is intentionally centralized and kept up to date.

- [`specs.md`](./specs.md) - Complete architecture and API reference.
- [`checklist.md`](./checklist.md) - Living development roadmap.

---

## Requirements

- PHP 8.0 or newer
- Composer & Git
- A web server (Apache with `mod_rewrite` enabled)
- Database: SQLite or MySQL

---

## Installation

### 1. Clone the repository
```bash
git clone https://github.com/qmisslin/archetype.git
cd archetype

```

### 2. Install dependencies

```bash
composer install

```

### 3. Setup Filesystem Permissions

The web server (e.g., `www-data` or `_www`) requires write access to several directories for logging, database management, and uploads:

```bash
# Example for Linux/macOS to allow PHP write access
mkdir -p data logs uploads
chmod -R 775 data logs uploads
# Ensure your web server user owns these directories
sudo chown -R _www:_www data logs uploads 

```

### 4. Environment configuration

```bash
cp .env.example .env

```

Edit `.env` and configure:

* `DB_TYPE` (SQLITE or MYSQL) 
* `SMTP_PORT` (Recommended: 587) and `SMTP_SECURE` (Recommended: tls)

---

## Database Setup (MySQL via Homebrew)

Archetype supports MySQL for production-grade environments.

### Install and Start MySQL

```bash
brew install mysql
brew services start mysql

```

### Create Database

```bash
mysql -u root -e "CREATE DATABASE archetype_db;"

```

---

## Web Server Configuration (Apache)

Archetype relies on `.htaccess` for URL rewriting to handle API endpoints.

### Enable Required Modules

Ensure `mod_rewrite` is enabled in your `httpd.conf`:

```apache
LoadModule rewrite_module lib/httpd/modules/mod_rewrite.so

```

### Enable .htaccess Support

Ensure `AllowOverride All` is set for the project directory to permit Archetype to manage routing and security:

```apache
<Directory "/path/to/archetype">
    AllowOverride All
    Require all granted
</Directory>

```

---

## Diagnostics and Testing

After installation, access the diagnostic dashboard to verify your configuration:

* **Diagnostic Dashboard**: `http://127.0.0.1:8080/admin.php`
* This tool verifies:
* Filesystem writability (Logs, Uploads, Data)
* Critical file presence (.env, vendor, composer)
* Apache URL rewriting for API routes
* Security folder protections



---

## Project Status

Core infrastructure is in place.

* [x] Core bootstrap and routing
* [x] Environment validation
* [x] Database layer (SQLite / MySQL)
* [x] Logging system and API
* [x] Email service (SMTP)
* [x] User authentication and login 
* [x] System Diagnostic Dashboard
* [ ] Content entries and advanced search
* [ ] Permissions and access control refinement

See [`checklist.md`](./checklist.md) for the detailed and up-to-date roadmap.

---

## License

MIT License.

You are free to use, modify, and distribute this project in both open-source and commercial contexts.

See [`LICENSE.md`](./LICENSE.md) for details.