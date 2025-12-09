# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Full-text search support for log messages (MySQL and PostgreSQL)
- OpenAPI 3.0 specification for the API (`openapi.yaml`)
- API rate limit response headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`)
- Comprehensive unit tests for models, services, and listeners
- WebhookDelivery factory for testing

### Changed
- Webhook threshold now supports all 8 PSR-3 log levels (debug, info, notice, warning, error, critical, alert, emergency)

### Fixed
- Webhook threshold validation was missing notice, warning, alert, and emergency levels

## [1.0.0] - 2024-12-09

### Added
- Initial release of Logger - a centralized log aggregation service
- **Log Ingestion API**: RESTful endpoint (`POST /api/ingest`) for receiving logs from any application
- **Multi-Project Support**: Manage logs from multiple applications with isolated project keys
- **Log Explorer**: Search, filter, and browse logs with pagination
- **Failing Controllers Report**: Identify error hotspots by controller
- **Retention Policies**: Configurable per-project log retention (7, 14, 30, 90 days, or infinite)
- **Webhook Notifications**: Slack, Discord, Mattermost, Microsoft Teams, and generic JSON webhook support
- **AI-Powered Analysis**: Optional OpenAI integration for intelligent log analysis
- **Dark Mode**: Full dark theme support
- **Health Check Endpoint**: `GET /api/health` for monitoring and uptime checks
- **User Management**: Admin and user roles with role-based access control
- **Scheduled Log Pruning**: Automatic cleanup of old logs based on retention policies
- Admin setup command (`php artisan setup:admin`)
- Log pruning command (`php artisan app:prune-logs`)

### Security
- Project API keys (64 characters) for secure log ingestion
- Webhook secrets for signature verification
- Rate limiting on API endpoints (1000 requests/minute)
- Rate limiting on webhook dispatching (30 webhooks/minute per project)
- Sensitive fields hidden from API responses (magic_key, webhook_secret, password)

[Unreleased]: https://github.com/ADMIN-INTELLIGENCE-GmbH/logger/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/ADMIN-INTELLIGENCE-GmbH/logger/releases/tag/v1.0.0
