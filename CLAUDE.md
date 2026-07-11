# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

A fresh Symfony 8.1 webapp skeleton (PHP >= 8.4) with Doctrine ORM, Twig, Symfony UX (Stimulus + Turbo via AssetMapper/importmap — no Node.js build step), Messenger (Doctrine transport), and the Security bundle scaffolded but not yet configured (in-memory user provider, no authenticators). The `src/Controller`, `src/Entity`, and `src/Repository` directories are empty placeholders; `src/Kernel.php` is the only source file so far.

## Commands

```bash
# Start the PostgreSQL database (compose.yaml defines a postgres:16-alpine service)
docker compose up -d

# Run the app (Symfony CLI local server; alternatively: php -S localhost:8000 -t public/)
symfony serve

# Run all tests (APP_ENV=test is forced by phpunit.dist.xml)
php bin/phpunit

# Run a single test file / single test method
php bin/phpunit tests/Path/To/SomeTest.php
php bin/phpunit --filter testMethodName

# Doctrine migrations
php bin/console doctrine:migrations:migrate   # apply
php bin/console doctrine:migrations:diff      # generate from entity changes

# Code generation (MakerBundle: make:controller, make:entity, make:form, etc.)
php bin/console make:<what>

# Clear cache
php bin/console cache:clear
```

## Architecture

- **Standard Symfony structure**: `src/` maps to the `App\` namespace (PSR-4), `tests/` to `App\Tests\`. Controllers, entities, and Doctrine repositories go in their conventional `src/` subdirectories; routes are discovered via PHP attributes on controllers (`config/routes.yaml`).
- **Database**: PostgreSQL 16 via Docker Compose. `DATABASE_URL` is set in `.env` (override locally in `.env.local`, never commit secrets there). Schema changes flow through entity attributes → `doctrine:migrations:diff` → files in `migrations/`.
- **Frontend**: AssetMapper + importmap (`importmap.php`), not Webpack/Vite. JS lives in `assets/` (Stimulus controllers in `assets/controllers/`, registered in `assets/controllers.json`); Twig templates in `templates/` extend `base.html.twig`. Add JS packages with `php bin/console importmap:require <pkg>`, not npm.
- **Async/messaging**: Messenger uses the Doctrine transport (`MESSENGER_TRANSPORT_DSN` in `.env`); consume with `php bin/console messenger:consume`.
- **Tests**: PHPUnit 13 is configured strictly — deprecations, notices, and warnings all fail the build (`failOnDeprecation`, `failOnNotice`, `failOnWarning` in `phpunit.dist.xml`).
- **Environments**: config in `config/packages/*.yaml` uses `when@dev`/`when@test` blocks; env-specific values come from `.env`, `.env.dev`, `.env.test` plus untracked `.env.local`.
