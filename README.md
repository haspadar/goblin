# Goblin

[![CI](https://github.com/haspadar/goblin/actions/workflows/piqule.yml/badge.svg)](https://github.com/haspadar/goblin/actions/workflows/piqule.yml)
[![Coverage](https://codecov.io/gh/haspadar/goblin/branch/main/graph/badge.svg)](https://codecov.io/gh/haspadar/goblin)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fhaspadar%2Fgoblin%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/haspadar/goblin/main)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen)](https://phpstan.org/)
[![Psalm](https://img.shields.io/badge/psalm-level%201-brightgreen)](https://psalm.dev)
[![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/haspadar/goblin?utm_source=oss&utm_medium=github&utm_campaign=haspadar%2Fgoblin&label=CodeRabbit%20Reviews)](https://coderabbit.ai)

Goblin guards your repo like a hoard of gold — nitpicking branches, hoarding commit rules, and grudgingly helping you file issues.

## What the Goblin does

- **Guards** — validates branches, commits, and hooks before anything gets through
- **Hoards** — loads Jira issues, renders ADF, generates daily reports
- **Grudgingly helps** — creates GitLab MRs, runs tests in Docker, formats output for humans and scripts

## Requirements

- PHP 8.3+
- curl extension

## Install

```bash
composer install
cp .goblin.example.php .goblin.php   # edit with your credentials
php bin/goblin install               # install git hooks
```

`install` embeds the Docker container name into the generated `pre-push` hook. The value is resolved in this order:

1. `--container=<name>` flag — wins over everything.
2. `services.<service>.container_name` read from the first detected compose file in the project root: `docker-compose.yml`, `docker-compose.yaml`, `compose.yml`, or `compose.yaml`.

The service key defaults to `app`. Override it with `--service=<name>` or by setting `compose-service` in `.goblin.php`. If the container cannot be determined and no flag is provided, `install` fails without creating any hooks.

```bash
php bin/goblin install --container=myapp          # force a specific container
php bin/goblin install --service=worker           # read services.worker.container_name
```

`install` is safe to run on repositories that already have hooks. For each of `commit-msg`, `pre-push`, `post-checkout` it does one of three things:

- file missing — a new hook is created;
- file contains `# BEGIN goblin` — the hook is left untouched (re-runs are no-ops);
- file exists without the marker — the goblin block is inserted right after the shebang, above any inherited body, so goblin checks run before a foreign hook can `exec` or `exit`.

## Usage

```bash
# Jira
php bin/goblin issue PROJ-1234              # show issue details
php bin/goblin issue PROJ-1234 description  # plain-text description
php bin/goblin issue PROJ-1234 raw          # raw JSON payload
php bin/goblin issue 1234                   # short key (project from branch)
php bin/goblin daily                        # daily activity report

# Jira releases (version → branch mapping)
php bin/jira-releases PLAT                        # list unreleased versions
php bin/jira-releases                             # project from current branch
php bin/jira-releases PLAT --verbose              # extra details

# Git validation (standalone scripts, called by hooks)
php bin/branch-check                              # validate current branch
php bin/commit-check .git/COMMIT_EDITMSG          # validate from file
php bin/commit-check "PRJ-42 Fix auth"            # validate text directly
php bin/commit-check --branch=PRJ-42 "PRJ-42 Fix auth"  # explicit branch

# GitLab merge requests
php bin/goblin mr create --source=PRJ-42-auth --target=main --title="PRJ-42 auth"
php bin/goblin mr view 123                  # view MR by IID
php bin/goblin mr list --state=opened       # list merge requests
php bin/goblin mr update 123 --draft        # mark as draft
php bin/goblin mr update 123 --ready        # mark as ready
```

## Branch rules

Version-to-branch mapping is configured via `branch-rules` in `.goblin.php`:

```php
'branch-rules' => [
    'beta'  => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
    'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
    'default' => 'dev',
],
```

Rules are evaluated top-to-bottom. Each rule assigns one release to a branch:

- **`match`** — regex pattern; supports `{var}` and `{var+N}` templates from earlier captures
- **`sort`** — `desc` (default) picks max version, `asc` picks min
- **`default`** — branch for all remaining unmatched releases

## Output

Terminal (TTY) sessions get colored human-readable text. Non-interactive
contexts (pipes, scripts, CI) get structured JSON — one object per line:

```json
{"level":"info","message":"loading issue PROJ-1234"}
```

Add `--debug` to any command to log timestamped entries to stderr.

## Testing

```bash
php bin/docker-test                                  # run tests in Docker
php bin/docker-test --parallel                       # run tests in parallel
php bin/docker-test --container=myapp                # target a specific container
```

The container name is taken from `--container`, falling back to `container` in `.goblin.php`. The generated `pre-push` hook passes `--container` automatically from the value resolved during `install`.

## Quality

Powered by [Piqule](https://github.com/haspadar/piqule) — PHPStan 9, Psalm 1, PHP-CS-Fixer, Infection.

```bash
vendor/bin/piqule check    # run all checks
vendor/bin/piqule fix      # auto-fix
```
