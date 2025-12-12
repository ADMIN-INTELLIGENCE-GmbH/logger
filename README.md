# Logger

[![Tests](https://github.com/ADMIN-INTELLIGENCE-GmbH/logger/actions/workflows/tests.yml/badge.svg)](https://github.com/ADMIN-INTELLIGENCE-GmbH/logger/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20)](https://laravel.com)

A centralized log aggregation service for collecting, storing, and analyzing application logs across multiple projects. Built with Laravel.

> **Looking for the Laravel package?**
>
> Use [Laravel Log Shipper](https://github.com/ADMIN-INTELLIGENCE-GmbH/laravel-log-shipper) to easily ship logs from your Laravel applications to this service.

## Overview

Logger provides a simple HTTP API for ingesting logs from any application, a web dashboard for viewing and filtering logs, and automated retention management. It supports webhook notifications for critical events and is designed to handle high-volume log ingestion.

## Features

- **Log Ingestion API**: RESTful endpoint for receiving logs from any application
- **Multi-Project Support**: Manage logs from multiple applications with isolated project keys
- **Log Explorer**: Search, filter, and browse logs with pagination
- **Failing Controllers Report**: Identify error hotspots by controller
- **Retention Policies**: Configurable per-project log retention (7, 14, 30, 90 days, or infinite)
- **Webhook Notifications**: Slack/Discord/Mattermost/Teams-compatible alerts for errors and critical events
- **AI-Powered Analysis**: Optional OpenAI integration for intelligent log analysis
- **Dark Mode**: Full dark theme support

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL 8.0+ or PostgreSQL 14+ or SQLite

## Installation

Clone the repository and install dependencies:

```bash
git clone <repository-url> logger
cd logger
composer install
npm install
```

Configure the environment:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure your database connection:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=logger
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run migrations and build assets:

```bash
php artisan migrate
npm run build
```

For local development:

```bash
npm run dev
php artisan serve
```

## Creating an Admin User

After installation, create an admin user account to access the dashboard:

```bash
php artisan setup:admin
```

This command will launch an interactive setup wizard that guides you through:
1. Entering the admin user's name
2. Setting the admin email address
3. Creating a secure password (minimum 8 characters)

You can also provide these values as options to automate the process:

```bash
php artisan setup:admin --name="John Doe" --email="admin@example.com" --password="secure-password-123"
```

Once created, you can log in to the dashboard at `/login` using the admin credentials.

## Configuration

### Creating a Project

1. Log in to the dashboard
2. Navigate to Projects
3. Click "New Project"
4. Configure the project name, retention policy, and optional webhook URL
5. Copy the generated project key

### Using the .env Configurator

Each project includes an interactive **.env Configurator** tool to help you generate the complete configuration needed for the Laravel Log Shipper package. This configurator:

- **Guides you through setup steps** for different configuration categories:
  - **Core Configuration**: `LOG_SHIPPER_ENABLED`, `LOG_SHIPPER_ENDPOINT`, `LOG_SHIPPER_KEY`, `LOG_SHIPPER_FALLBACK`
  - **Queue Settings**: `LOG_SHIPPER_QUEUE`, `LOG_SHIPPER_QUEUE_NAME`
  - **Batch Shipping**: `LOG_SHIPPER_BATCH_ENABLED`, `LOG_SHIPPER_BATCH_DRIVER`, `LOG_SHIPPER_BATCH_SIZE`, `LOG_SHIPPER_BATCH_INTERVAL`
  - **Status Monitoring**: `LOG_SHIPPER_STATUS_ENABLED`, `LOG_SHIPPER_STATUS_ENDPOINT`, `LOG_SHIPPER_STATUS_INTERVAL`

- **Provides helpful descriptions** for each setting to explain what it does and what values it accepts
- **Offers dropdown menus** for boolean and driver selection fields to make configuration easier
- **Pre-fills values** like the endpoint and project key automatically
- **Generates ready-to-copy output** that you can paste directly into your `.env` file

To access it, go to your project's settings page and scroll to the ".env Configurator" section.

### Log Retention

Each project can have its own retention policy. The `app:prune-logs` command removes logs older than the configured retention period. Schedule it in your crontab:

```bash
* * * * * cd /path/to/logger && php artisan schedule:run >> /dev/null 2>&1
```

Or run manually:

```bash
php artisan app:prune-logs
php artisan app:prune-logs --dry-run  # Preview without deleting
```

## API Usage

### OpenAPI Specification

A complete OpenAPI 3.0 specification is available at [`openapi.yaml`](openapi.yaml). You can view interactive API documentation at [https://admin-intelligence-gmbh.github.io/logger/](https://admin-intelligence-gmbh.github.io/logger/) or import the spec into tools like [Swagger UI](https://swagger.io/tools/swagger-ui/), [Postman](https://www.postman.com/), or [Insomnia](https://insomnia.rest/) to explore the API interactively.

### Rate Limiting

The log ingestion endpoint is rate-limited to **1000 requests per minute per IP address**. Rate limit information is returned in response headers:

| Header | Description |
|--------|-------------|
| `X-RateLimit-Limit` | Maximum requests allowed per window |
| `X-RateLimit-Remaining` | Requests remaining in current window |
| `X-RateLimit-Reset` | Unix timestamp when the rate limit resets |

### Ingesting Logs

Send a POST request to the ingestion endpoint with your project key:

```bash
curl -X POST https://your-logger-instance.com/api/ingest \
  -H "Content-Type: application/json" \
  -H "X-Project-Key: your-64-character-project-key" \
  -d '{
    "level": "error",
    "message": "Database connection failed",
    "controller": "App\\Http\\Controllers\\UserController",
    "route_name": "users.show",
    "method": "GET",
    "user_id": "12345",
    "context": {
      "exception": "PDOException",
      "sql": "SELECT * FROM users WHERE id = ?"
    }
  }'
```

### Request Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `level` | string | Yes | PSR-3 log level: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency` |
| `message` | string | Yes | Log message (max 65535 characters) |
| `channel` | string | No | Logging channel name |
| `datetime` | string | No | Original log timestamp (Y-m-d H:i:s.u format) |
| `context` | object | No | Additional structured data |
| `extra` | object | No | Monolog processor data |
| `controller` | string | No | Controller class name |
| `route_name` | string | No | Laravel route name |
| `method` | string | No | HTTP method (GET, POST, etc.) |
| `request_url` | string | No | Full request URL |
| `user_id` | string | No | User identifier |
| `ip_address` | string | No | Client IP (auto-detected if omitted) |
| `user_agent` | string | No | Browser/client user agent |
| `app_env` | string | No | Application environment |
| `app_debug` | boolean | No | Whether debug mode is enabled |
| `referrer` | string | No | HTTP Referer header |

### Response

Success (201):
```json
{
  "success": true,
  "message": "Log entry created successfully",
  "log_id": 12345
}
```

Error (401):
```json
{
  "error": true,
  "message": "Invalid project key or project is inactive"
}
```

Validation Error (422):
```json
{
  "error": true,
  "message": "Validation failed",
  "errors": {
    "level": ["The level field is required."]
  }
}
```

## Laravel Integration Example

Create a custom log channel in your application:

```php
// config/logging.php
'channels' => [
    'logger' => [
        'driver' => 'monolog',
        'handler' => App\Logging\LoggerHandler::class,
        'with' => [
            'endpoint' => env('LOGGER_ENDPOINT'),
            'projectKey' => env('LOGGER_PROJECT_KEY'),
        ],
    ],
],
```

```php
// app/Logging/LoggerHandler.php
namespace App\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class LoggerHandler extends AbstractProcessingHandler
{
    public function __construct(
        private string $endpoint,
        private string $projectKey,
    ) {
        parent::__construct();
    }

    protected function write(LogRecord $record): void
    {
        Http::withHeaders(['X-Project-Key' => $this->projectKey])
            ->post($this->endpoint . '/api/ingest', [
                'level' => strtolower($record->level->name),
                'message' => $record->message,
                'context' => $record->context,
            ]);
    }
}
```

## Testing

Run the test suite:

```bash
php artisan test
```

Run specific tests:

```bash
php artisan test --filter=IngestApiTest
php artisan test --filter=PruneLogsTest
```

## Project Structure

```
app/
  Console/Commands/     Artisan commands (PruneLogs)
  Events/               Event classes (LogCreated)
  Http/Controllers/     Web and API controllers
  Listeners/            Event listeners (WebhookDispatcher)
  Models/               Eloquent models (Project, Log, User)
database/
  factories/            Model factories for testing
  migrations/           Database schema
  seeders/              Sample data seeders
resources/views/
  layouts/              Blade layouts
  projects/             Project views (dashboard, logs, settings)
routes/
  api.php               API routes
  web.php               Web routes
tests/
  Feature/              Feature tests
  Unit/                 Unit tests
```

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Credits

[Julian Billinger](https://github.com/j-bill) | [ADMIN INTELLIGENCE GmbH](https://admin-intelligence.com)

## Support

For support, please contact support@admin-intelligence.de or open an issue on GitHub.
