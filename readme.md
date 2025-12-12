# Archetype

Archetype is a PHP-based headless CMS designed around a schema-driven API.
It exposes content, users, files, and system operations exclusively through HTTP endpoints, without any built-in UI.

The API is role-based (`ADMIN`, `EDITOR`, `PUBLIC`) and uses token authentication.
All content is validated against versioned schemas and stored as JSON.

This repository currently contains:
- `spec.md`: the full functional and technical specification of the API
- the expected project structure for a future implementation

This project is intended to be:
- framework-agnostic
- easy to self-host
- suitable for custom admin panels and frontends

---

## Repository structure (target)

See `spec.md` for the full architecture and API documentation.

---

## Status

Specification only.  
No implementation yet.

---

## License

To be defined.
