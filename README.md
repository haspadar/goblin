# Goblin

CLI tool for Jira and GitLab workflow automation.

## Features

- Load Jira issue details with ADF-to-plaintext rendering
- Validate branch names against Jira Fix Versions
- Validate commit messages against branch issue keys
- Generate daily activity reports from Jira
- Create Jira issues (bug, tech) with pre-filled fields
- Git hooks for automated checks

## Requirements

- PHP 8.3+
- curl extension

## Install

```bash
composer install
cp .goblin.example.php .goblin.php   # edit with your credentials
```

## Usage

```bash
php bin/goblin issue PROJ-1234              # show issue details
php bin/goblin issue PROJ-1234 description  # plain-text description
php bin/goblin issue create --type=bug --summary="Login page returns 500"
php bin/goblin daily                       # daily activity report
php bin/goblin branch-check                # validate current branch
php bin/goblin commit-check                # validate commit message
php bin/goblin install                     # install git hooks
```

## Quality

Powered by [Piqule](https://github.com/haspadar/piqule) — PHPStan 9, Psalm 1, PHP-CS-Fixer, Infection.

```bash
vendor/bin/piqule check    # run all checks
vendor/bin/piqule fix      # auto-fix
```
