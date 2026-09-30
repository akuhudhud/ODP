# ODP

ODP is the internal development codename for the project.

## Status

Foundation stage — v0.1.0.

## v0.1.0 Scope

This version establishes the project structure and development documentation only.

No business logic is included yet:

- No authentication
- No order engine
- No fare/price engine
- No payment
- No wallet
- No vendor system
- No delivery workflow
- No personal shopper workflow

## Structure

- `backend/` — backend application
- `user-app/` — user mobile application
- `runner-app/` — runner mobile application
- `admin/` — administration system
- `database/` — database-related assets
- `docs/` — architecture, API and decisions
- `tests/` — test assets

## Development Principles

1. Build the foundation first.
2. Keep modules separated.
3. Avoid premature business logic.
4. Use version control from the beginning.
5. GitHub is the source of truth.
6. Keep development-facing naming under the ODP codename.

## Versioning

Semantic-versioning style is used.

Current version: `0.1.0`
