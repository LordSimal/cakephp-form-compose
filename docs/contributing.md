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

The site teaches CakePHP developers who are new to FormCompose. Keep the homepage
short and connect a control call to its PHP element before detailed setup.

- **Start here** explains the problem, walks through a minimal article form, and
  introduces the rendering model. Keep optional configuration out of the first form.
- **Build forms** contains focused recipes for values, choices, associations,
  field parts, and themes.
- **Reusable forms** explains field definitions, attributed blueprints, and nested
  layouts together. Keep reuse optional and explain each object's responsibility.
- **API reference** owns complete signatures, return values, options, defaults,
  precedence, and failure contracts. Guides link to this material rather than
  repeating full tables.
- **Advanced and project** contains custom controls, service extensions, blueprint
  injection/discovery, and contributor setup.

Use an article form for the introductory walkthrough and focused examples for
other tasks. Label files, identify assumed application variables, and include
imports. Prefer minimal working examples, introducing options only when needed for
the task. Show the corresponding PHP element whenever selecting a new template.
Keep existing page URLs stable and update navigation, local links, and README
entry points together. Check examples against implementation and behavior tests.

Use VitePress code groups, callouts, and local search. Keep generated output out
of commits. Documentation-only changes require content/example review and
`npm ci` plus `npm run docs:build`; they do not require the PHP quality workflow.
