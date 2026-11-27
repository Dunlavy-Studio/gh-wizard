# gh-wizard

![Tests](https://github.com/Dunlavy-Studio/gh-wizard/actions/workflows/tests.yml/badge.svg)
![Licence](https://img.shields.io/github/license/Dunlavy-Studio/gh-wizard)
![Release](https://img.shields.io/github/v/release/Dunlavy-Studio/gh-wizard)

An interactive wizard for GitHub CLI, designed to make everyday GitHub workflows simpler, more intuitive and more accessible.

Inspired by the Laravel installer's terminal experience, gh-wizard provides guided menus, interactive selections and confirmation prompts without requiring you to memorise complex commands.

## Features

- Interactive terminal menus.
- Dynamic GitHub account and organisation selection.
- Create public and private repositories.
- Clone newly created repositories locally.
- Open repositories in your browser.
- Publish existing local Git repositories.
- Local repository validation before publishing.
- Private repositories by default.
- Confirmation before creating or publishing repositories.

## Requirements

- PHP 8.4.1 or newer.
- Git.
- GitHub CLI (`gh`).
- An authenticated GitHub account.
- macOS or Linux.

Composer is not required when installing a packaged release.

Native Windows support is not currently available.

## Installation

```bash
gh extension install Dunlavy-Studio/gh-wizard
```

Launch the wizard:

```bash
gh wizard
```

If you are not already authenticated:

```bash
gh auth login
```

## Updating

```bash
gh extension upgrade wizard
```

## Uninstalling

```bash
gh extension remove wizard
```

## Development

Clone the repository:

```bash
git clone https://github.com/Dunlavy-Studio/gh-wizard.git
cd gh-wizard
composer install
```

Install the local development extension:

```bash
gh extension install .
```

Run all quality checks:

```bash
composer check
```

Format the source code:

```bash
composer format
```

## Roadmap

- Repository browsing and management.
- Issue management.
- Pull request workflows.
- GitHub Actions integration.
- GitHub Projects integration.
- Reusable repository presets.

## Contributing

Contributions, bug reports and feature suggestions are welcome.

Please see [CONTRIBUTING.md](CONTRIBUTING.md) before submitting a pull request.

## Privacy

gh-wizard does not collect telemetry or require additional accounts. It uses your existing GitHub CLI authentication to perform GitHub operations.

## Licence

Released under the [MIT Licence](LICENSE).

Built and maintained by [Dunlavy Studio](https://dunlavystudio.co.uk).

An independent open-source project. Not affiliated with or endorsed by GitHub.