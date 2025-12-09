# Contributing to Logger

Thank you for considering contributing to Logger! We welcome contributions from the community.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment.

## How to Contribute

### Reporting Bugs

If you find a bug, please open an issue on GitHub with:

1. A clear, descriptive title
2. Steps to reproduce the issue
3. Expected behavior vs. actual behavior
4. Your environment details (PHP version, database, OS)
5. Any relevant logs or screenshots

### Suggesting Features

We welcome feature suggestions! Please open an issue with:

1. A clear description of the feature
2. The problem it solves
3. Any implementation ideas you have

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Install dependencies**: `composer install && npm install`
3. **Run tests**: `php artisan test`
4. **Follow coding standards**: Run `./vendor/bin/pint` for PHP code formatting
5. **Write tests** for new features or bug fixes
6. **Update documentation** if needed
7. **Submit your PR** with a clear description

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/logger.git
cd logger

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start development server
composer dev
```

## Coding Standards

### PHP

- Follow PSR-12 coding standards
- Use Laravel conventions and best practices
- Run `./vendor/bin/pint` before committing
- Add type hints to all methods
- Document complex logic with comments

### JavaScript

- Use ES6+ syntax
- Follow existing code patterns
- Use Alpine.js for interactive components

### Testing

- Write feature tests for new endpoints
- Write unit tests for complex logic
- Ensure all tests pass before submitting PR
- Aim for meaningful test coverage

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=IngestApiTest

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

## Commit Messages

Use clear, descriptive commit messages:

- `feat: add webhook retry functionality`
- `fix: resolve log pagination issue`
- `docs: update API documentation`
- `test: add tests for log pruning`
- `refactor: improve dashboard query performance`

## Pull Request Process

1. Update the README.md if needed
2. Update the CHANGELOG.md with your changes
3. Ensure all tests pass
4. Request review from maintainers
5. Address any feedback

## Questions?

If you have questions, feel free to:

- Open a discussion on GitHub
- Email us at support@admin-intelligence.de

Thank you for contributing!
