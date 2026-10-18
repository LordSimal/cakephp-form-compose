---
title: Contributing
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

## Browser and HTTP regressions

After `composer install`, install the development-only browser tooling:

```sh
npm ci
npx playwright install chromium
npm run test:browser
```

On Linux CI, use `npx playwright install --with-deps chromium` to install the
browser's system libraries too. The suite starts a local PHP server on port 8765
and tests native constraint validation, actual `FormData`, ID associations, native summary-link navigation, and
multipart submissions through CakePHP's CSRF middleware and FormProtectionComponent.
It also checks rejection of extra fields, altered locked values, and missing CSRF
tokens. CI runs this as a separate test job. Browser tooling is not a
runtime dependency of the plugin or rendered forms.

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

### Page metadata

Give every documentation page a unique `title` and `description` in its YAML
frontmatter. Keep the homepage title and hero explicit about CakePHP. Ordinary
page titles receive the FormCompose suffix; the homepage sets `titleTemplate: false`
to use its complete title directly.

The VitePress configuration generates canonical URLs, Open Graph and Twitter
metadata, and a sitemap for `https://josbeir.github.io/cakephp-form-compose/`.
Page metadata also updates during client-side navigation. Keep the public
hostname and repository base path aligned when changing hosting, and preserve
existing `.html` URLs. The generated 404 page is marked `noindex`.
The public asset directory is excluded from page discovery so asset documentation
does not become a reader-facing page or sitemap entry.

The SVG logo is the favicon; its PNG export supplies the social sharing image.
Verify the built HTML and `sitemap.xml` when changing metadata configuration.

## Documentation structure

The site teaches CakePHP developers who are new to FormCompose. Keep the homepage
short and connect a control call to its PHP element before detailed setup.

Source directories mirror the sidebar groups. Keep `docs/index.md` as the homepage;
put other pages in the section that owns the topic:

| Sidebar section | Source directory | Content |
| --- | --- | --- |
| Start here | `docs/start-here/` | The problem, first form, and migration. Keep optional configuration out of the first form. |
| Build your forms | `docs/forms/` | Recipes for values, choices, associations, custom field types, error summaries, and POST actions. |
| Markup and styling | `docs/markup/` | The rendering model, field parts, templates, themes, variants, and template debugging. |
| Reusable forms | `docs/reusable-forms/` | Reusable configuration, blueprints, nested layouts, and repeated embeds. Keep reuse optional. |
| API reference | `docs/reference/` | Complete signatures, return values, options, defaults, precedence, and failure contracts. |
| Advanced and project | `docs/advanced/` | Service extensions, blueprint injection/discovery, and contributor setup. |

Guides link to the reference for complete contracts rather than repeating its
tables. Keep configuration, resolved state, and rendering responsibilities clear.

### Source paths and published URLs

VitePress `rewrites` in `docs/.vitepress/config.ts` map these source paths to the
existing public routes. For example, `docs/markup/variants.md` is still published
at `/variants.html`. Internal links use published routes such as
`/variants#layers-take-priority-before-specificity`, independently of the source
directory. VitePress adds the repository base path and `.html` extension.

When adding a page, put it in the matching directory, add its rewrite, and add
its sidebar entry. When moving a page, update its rewrite's source path and keep
the destination stable. GitHub edit links use the actual source file path.

Use an article form for the introductory walkthrough and focused examples for
other tasks. Label files, identify assumed application variables, and include
imports. Prefer minimal working examples, introducing options only when needed for
the task. Show the corresponding PHP element whenever selecting a new template.
Keep existing page URLs stable and update navigation, local links, and README
entry points together. Check examples against implementation and behavior tests.

Use VitePress code groups, callouts, and local search. Keep generated output out
of commits. Documentation-only changes require content/example review and
`npm ci` plus `npm run docs:build`; they do not require the PHP quality workflow.
