# Archetype

Archetype is a PHP-based headless CMS designed around a schema-driven API.
It exposes content, users, files, and system operations exclusively through HTTP endpoints, without any built-in UI.

The API is role-based (`ADMIN`, `EDITOR`, `PUBLIC`) and uses token authentication.
All content is validated against versioned schemas and stored as JSON.

This repository currently contains:
- `specs.md`: the full functional and technical specification of the API
- the expected project structure for a future implementation

This project is intended to be:
- framework-agnostic
- easy to self-host
- suitable for custom admin panels and frontends

---

## Documentation

See `specs.md` for the full architecture and API documentation.

- [Full API specifications](./specs.md)  
  - Overall architecture and file organization  
  - Environment and configuration  
  - Database schema and relations  
  - Authentication, roles, and permissions model  
  - Detailed API routes (inputs, outputs, roles)  
  - Data schemes and validation rules  
  - Entries lifecycle and search AST  
  - Uploads, logs, trackers, and email services  


---
## Getting Started

### Prerequisites

* PHP (v8.0 or newer recommended)
* Composer
* Git

### Project Setup

1.  **Clone the repository:**
    ```bash
    git clone [your-repo-url]
    cd archetype-api
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Configure Environment Variables:**
    Create your actual environment file by copying the example:
    ```bash
    cp .env.example .env
    ```
    Then, edit the `.env` file and fill in the necessary configuration (database type, credentials, email settings, etc.).
    
    > **Note for Local Testing (SQLite):** For quick local setup, ensure your `.env` is configured to use SQLite:
    > ```ini
    > DB_TYPE=SQLITE
    > ```

---

### Local Development Server

The simplest way to run the API locally is by using PHP's built-in web server.

1.  **Start the server:**
    Run this command from the project root directory:
    ```bash
    php -S localhost:8080 -t .
    ```

2.  **Access the API:**
    The API is now running at `http://localhost:8080`.

    * **Root check:** `curl http://localhost:8080/`
    * **Route check (404 expected):** `curl http://localhost:8080/api/nonexistent`

---

## Status

- Specification phase only  
- No implementation yet  
- Development roadmap defined in [Development checklist](./checklist.md)  
  - Structured, step-by-step plan to implement the project  
  - Covers bootstrap, logging, core features, security, and hardening  

---

## License

This project is licensed under the **MIT License**.

You are free to use, modify, distribute, and integrate this software in both open-source and commercial projects, with minimal restrictions.

See [LICENSE.md](./LICENSE.md) for full license text.
