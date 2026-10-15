---
description: Run the PHP checks, enforce coverage, and work on the VitePress documentation site.
---

# Contributing

## PHP checks

Use PHP 8.5+ and the locked CakePHP 6 development build. Enable PDO SQLite for the association integration tests. They use an in-memory
database to verify choice queries and CakePHP entity marshalling.

```sh
composer install
composer check
composer test-coverage
```

`composer check` runs PHPUnit, Mago analysis and linting using the CakePHP preset,
a Rector dry run, and Mago format checks. CI runs the same command and fails when
Rector proposes changes.

`composer format` applies formatting; `composer lint-fix` applies safe lint fixes.
`composer rector` previews refactorings without changing files. Run
`composer rector-fix` to apply refactorings and then format with Mago. Review the
diff and rerun `composer check` and `composer test-coverage` before submitting it.

Rector targets the PHP version declared in Composer and checks `src/` and `tests/`.
Mago remains the formatting and linting authority. Rector skips transformations
that conflict with CakePHP's assertion style, explicit string casts, or Mago's
mixed-value annotations. It also preserves the existing fluent return contracts
and normalized paths required by CakePHP's template-path validation.

Coverage requires Xdebug coverage mode or PCOV. All production PHP is included,
with a gate enforcing **strictly greater than 90% line coverage**. CI uploads the
Clover report to Codecov using GitHub OIDC. Upload errors are nonblocking; the
local coverage gate remains mandatory. Codecov must have the repository enabled
to populate its coverage badge.

## Documentation site

The site uses a pinned VitePress 2 preview. Use Node.js 24 or later:

```sh
npm ci
npm run docs:dev
```

Build and preview the published output with:

```sh
npm run docs:build
npm run docs:preview
```

The build checks internal links. Documentation changes build on pull requests and
deploy to GitHub Pages from `main`. Keep generated output and installed Node
dependencies out of commits.

## Documentation structure

Keep the homepage brief, with a short introduction and a few tabbed code examples.
The learning pages explain the problem and rendering model; recipes show how to
build forms and themes; reference pages hold the detailed contracts.

Keep examples connected: show how a theme prefix, a field type, and an element
path relate to each other. Use built-in VitePress features where they make the
content easier to follow. Keep the README short and link readers to this site.
