# Security Policy

## Supported Versions

We actively maintain and provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please send an email to [security@admin-intelligence.de](mailto:security@admin-intelligence.de) with:

1. A description of the vulnerability
2. Steps to reproduce the issue
3. Potential impact of the vulnerability
4. Any suggested fixes (optional)

### What to Expect

- **Acknowledgment**: We will acknowledge receipt of your report within 48 hours.
- **Updates**: We will keep you informed of our progress in addressing the issue.
- **Resolution**: We aim to resolve critical vulnerabilities within 7 days.
- **Credit**: We will credit you in the release notes (unless you prefer to remain anonymous).

### Scope

This security policy applies to:

- The Logger application codebase
- Official Docker images and deployment configurations
- The HTTP API endpoints

### Out of Scope

- Vulnerabilities in third-party dependencies (please report these to the respective maintainers)
- Issues in user-configured deployment environments
- Denial of service attacks that require authenticated access

## Security Best Practices

When deploying Logger in production:

1. **Use HTTPS**: Always serve the application over HTTPS
2. **Secure Database**: Use strong database credentials and restrict network access
3. **Environment Variables**: Never commit `.env` files; use secure secret management
4. **Regular Updates**: Keep dependencies updated with `composer update` and `npm update`
5. **Disable Registration**: Keep `REGISTRATION_ENABLED=false` unless you need public registration
6. **Rate Limiting**: The API includes rate limiting, but consider additional protection behind a reverse proxy
7. **Webhook Secrets**: Use webhook secrets for signature verification

## Security Features

Logger includes several security features:

- **API Key Authentication**: Projects are authenticated via secure 64-character random keys
- **Rate Limiting**: API endpoints are rate-limited to prevent abuse
- **CSRF Protection**: Web routes are protected against cross-site request forgery
- **Webhook Signatures**: HMAC signatures for webhook deliveries
- **Input Validation**: All inputs are validated and sanitized
- **SQL Injection Prevention**: Uses Eloquent ORM with parameterized queries

Thank you for helping keep Logger and our users safe!
