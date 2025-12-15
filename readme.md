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
