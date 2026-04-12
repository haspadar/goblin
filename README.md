# Goblin

[![CI](https://github.com/haspadar/goblin/actions/workflows/piqule.yml/badge.svg)](https://github.com/haspadar/goblin/actions/workflows/piqule.yml)
[![Coverage](https://codecov.io/gh/haspadar/goblin/branch/main/graph/badge.svg)](https://codecov.io/gh/haspadar/goblin)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fhaspadar%2Fgoblin%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/haspadar/goblin/main)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen)](https://phpstan.org/)
[![Psalm](https://img.shields.io/badge/psalm-level%201-brightgreen)](https://psalm.dev)
[![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/haspadar/goblin?utm_source=oss&utm_medium=github&utm_campaign=haspadar%2Fgoblin&label=CodeRabbit%20Reviews)](https://coderabbit.ai)

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
