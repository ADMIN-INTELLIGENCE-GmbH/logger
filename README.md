# Logger

A centralized log aggregation service for collecting, storing, and analyzing application logs across multiple projects. Built with Laravel.

> **📦 Looking for the Laravel package?** Use [Laravel Log Shipper](https://github.com/ADMIN-INTELLIGENCE-GmbH/laravel-log-shipper) to easily ship logs from your Laravel applications to this service.

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
| `level` | string | Yes | Log level: `debug`, `info`, `error`, `critical` |
| `message` | string | Yes | Log message (max 65535 characters) |
| `context` | object | No | Additional structured data |
| `controller` | string | No | Controller class name |
| `route_name` | string | No | Laravel route name |
| `method` | string | No | HTTP method (GET, POST, etc.) |
| `user_id` | string | No | User identifier |
| `ip_address` | string | No | Client IP (auto-detected if omitted) |

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

- [Julian Billinger](https://github.com/jbillinger)
- [ADMIN INTELLIGENCE GmbH](https://github.com/ADMIN-INTELLIGENCE-GmbH)

## Support

For support, please contact support@admin-intelligence.de or open an issue on GitHub.
