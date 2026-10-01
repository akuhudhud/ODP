# Changelog

All notable changes to this project are documented here.

## [0.4.0] - 2026-10-01

### Added
- Standard API v1 response structure.
- Success response format with `status`, `message` and `data`.
- Error response format with `status`, `message` and `data`.
- API v1 response documentation under `docs/api/v1.md`.

### Changed
- Updated `HealthController` to use the API v1 response standard.
- Updated API health feature test to validate the new response structure.

### Validation
- Full Laravel test suite: 3 tests passed.

### Scope
- API infrastructure only.
- No business logic implemented.

## [0.3.0] - 2026-10-01

### Added
- API v1 foundation.
- API health endpoint at `GET /api/v1/health`.
- `HealthController` under the API v1 namespace.
- Automated feature test for the API health endpoint.

### Validation
- API health test passed.
- Full Laravel test suite: 3 tests passed.

### Scope
- API infrastructure only.
- No business logic implemented.
- No authentication workflow.
- No order engine.
- No fare/price engine.
- No payment or wallet system.
- No vendor system.
- No delivery or personal shopper workflow.

## [0.2.0] - 2026-09-30

### Added
- Laravel 10 backend foundation under `backend/`.
- Laravel application structure.
- Composer dependency management.
- Laravel environment configuration.
- Application key generation.
- Initial Laravel test suite.

### Validation
- Laravel Framework 10.50.3 verified.
- PHP 8.4.15 verified.
- Composer 2.10.3 verified.
- PHPUnit tests: 2 passed.

### Scope
- Backend foundation only.
- No business logic implemented.
- No order engine.
- No fare engine.
- No payment or wallet system.
- No vendor module.
- No delivery or personal shopper logic.

## [0.1.0] - 2026-09-30

### Added
- Initial ODP repository structure.
- Backend, user app, runner app and admin directories.
- Database, documentation and tests directories.
- README.
- Development log.
- Versioning system.
- Root `.gitignore`.

### Scope
- Foundation structure and documentation only.
- Business logic intentionally excluded.
