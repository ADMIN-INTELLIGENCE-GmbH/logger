# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Project Permissions**: Per-project access control (view/edit) with user assignments
  - Project-user membership table with permission levels
  - Project policy enforcement for dashboards, logs, and settings
  - User admin UI to assign project access
- **Daily Digest Feature**: Scheduled email summaries of project logs with user preferences
  - User profile settings for daily digest configuration (enable/disable, time preference)
  - Timezone support for users to receive digests at their preferred local time
  - `SendDailyDigests` command to send daily email summaries based on user preferences
  - `DailyDigestService` to gather and format data for digest emails
  - Test command `SendTestDigest` for testing digest functionality
  - Comprehensive tests for daily digest functionality
- **Tag Management System**: Organize projects with tags
  - Tag model and database support with many-to-many project relationships
  - Autocomplete functionality for tag input
  - Tag search API endpoint
- **CORS Configuration**: Per-project allowed domains configuration for cross-origin requests
  - Configurable whitelist of domains that can send logs to each project
  - Enhanced security for API access control
- **Server Status Monitoring**: Real-time server metrics and statistics
  - Global dashboard with aggregated statistics across all projects
  - Per-project server statistics (CPU, memory, disk usage)
  - Server stats API endpoint (`POST /api/stats`) for projects to report their status
  - Application info, system info, queue & database status display
  - Storage & cache usage visualization with progress bars
- **Environment Configurator**: Step-by-step .env file generator in project settings
  - Interactive configuration options based on selected features
  - Copy to clipboard functionality for generated .env content
  - API key display and copy functionality
- **Log Management Enhancements**:
  - Copy to clipboard functionality for log details
  - Individual log deletion capability
  - Bulk truncate logs feature (delete all logs for a project)
  - Dashboard preferences for users (customizable dashboard widgets)
- **Webhook Enhancements**:
  - Webhook format selection (Slack, Discord, Mattermost, Microsoft Teams, Generic JSON)
  - Webhook delivery tracking with WebhookDelivery model
  - Webhook secret regeneration functionality
  - Test webhook delivery capability
  - Enhanced webhook notification formatting for different platforms
- **UI/UX Improvements**:
  - Material Design Icons integration for better aesthetics
  - Favicon support
  - Enhanced project dashboard with memory and CPU usage metrics
  - Improved navigation and layout consistency
  - Server resource usage bars for memory and disk space
- **Developer Experience**:
  - Log shipper configuration support
  - GitLab CI/CD pipeline with Node.js setup and asset build steps
  - Issue templates for bug reports and feature requests
  - Interactive API documentation (`docs/index.html`)
  - Laravel badge in README
- Full-text search support for log messages (MySQL and PostgreSQL)
- OpenAPI 3.0 specification for the API (`openapi.yaml`)
- API rate limit response headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`)
- Comprehensive unit tests for models, services, and listeners
- WebhookDelivery factory for testing

### Changed
- Project creation and settings access now enforced via permissions (admins can create, edit permissions gate edits)
- Daily digest content is scoped to the projects a user can access
- Webhook threshold now supports all 8 PSR-3 log levels (debug, info, notice, warning, error, critical, alert, emergency)
- Request signature hashing upgraded to SHA-256 for improved security
- Enhanced IngestLogRequest validation with support for server statistics
- Log column sizes adjusted for user agent and referrer fields
- Improved OpenAI service with better error handling and response formatting
- Enhanced webhook dispatcher with support for multiple notification formats

### Fixed
- Webhook SSL handshake failures with Cloudflare-proxied domains by using IP-based resolution
- Log Explorer "View" interaction to prevent accidental modal opening when selecting rows
- Failing Controllers method breakdown showing "unknown" for errors by improving stack trace parsing logic
- Webhook threshold validation was missing notice, warning, alert, and emergency levels
- URL safety checks enhanced for better testability in non-production environments
- Whitespace and formatting cleaned up across multiple controllers and configuration files
- Vite manifest handling in CI/CD to prevent test failures
- Composer dependency cleanup (removed unused laravel-log-shipper)
- Test redirects updated to point to dashboard
- PHP version and dependencies updated in CI/CD test stage

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
