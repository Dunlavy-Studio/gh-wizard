# Contributing to gh-wizard

Thank you for your interest in contributing to gh-wizard.

We welcome bug reports, feature suggestions, documentation improvements and code contributions.

## Getting started

1. Fork the repository.
2. Clone your fork locally.
3. Run `composer install`.
4. Create a branch for your changes.
5. Implement and test your changes.
6. Submit a pull request.

## Development principles

- Keep the application lightweight.
- Avoid unnecessary dependencies and abstractions.
- Use GitHub CLI for GitHub operations wherever possible.
- Prefer safe defaults.
- Request confirmation before modifying repositories.
- Keep user-facing error messages clear and helpful.
- Never collect unnecessary user data.

## Code quality

All contributions must pass the following checks:

```bash
composer lint
composer analyse
composer test
```

Alternatively, run all checks together:

```bash
composer check
```

Use Laravel Pint to format your changes:

```bash
composer format
```

New functionality should include appropriate automated tests. Tests must not perform unintended operations against real GitHub repositories.

## Pull requests

Please keep pull requests focused on a single feature, improvement or bug fix.

Include a clear description of the changes, the reason for them and any relevant testing information.

For substantial architectural changes or new features, please open an issue for discussion first.

## Licence

By contributing to gh-wizard, you agree that your contributions will be distributed under the project's MIT Licence.