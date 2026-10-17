# Contributor and agent instructions

These instructions apply to the entire plugin repository.

## Purpose and architecture

- Build a CakePHP plugin for composable form binding and rendering.
- Render form, button, field, choice, and hidden-input markup through the shared PHP template system.
- Prioritize bound fields and application-owned markup. Keep reusable PHP form definitions an optional layer.
- Separate field configuration, resolved field state, rendering, and per-form submission/security bookkeeping.
- Reuse CakePHP context providers, validation metadata, routing, request abstractions, and form protection through public APIs.
- Keep inspection of field state free of rendering and security-registration side effects. Register submitted inputs when rendering them, including custom controls.
- Support native PHP templates, independently renderable field parts, and custom HTML structures without requiring helper subclassing.
- Keep CSS and JavaScript frameworks optional. Preserve arbitrary integration attributes and use complete, statically discoverable Tailwind class names.
- Keep validation and persistence in their existing application layers. Do not introduce a competing validator or entity-saving lifecycle.

## CakePHP and PHP practices

- Declare supported PHP and CakePHP versions explicitly in Composer and test that supported matrix in CI. Use modern PHP features within the declared minimum version.
- Follow CakePHP conventions and the CakePHP Mago preset. Use `declare(strict_types=1);` in PHP classes and executable support scripts.
- Use PHP alternative control syntax (`if: ... endif;`, `foreach: ... endforeach;`) only when the block wraps HTML across PHP tags. Use normal braces for blocks containing only PHP logic, including inside templates and documentation examples.
- Use a dedicated plugin namespace, PSR-4 autoloading, and Composer package type `cakephp-plugin`.
- Follow the standard plugin structure: `src/`, `templates/`, `config/`, and `tests/`, adding directories only when needed. Keep tests under `tests/TestCase/` and fixtures under `tests/Fixture/`.
- Use CakePHP's plugin lifecycle and dependency injection facilities. Avoid global state, hidden service lookups, and host-application-specific assumptions.
- Prefer small cohesive objects and composition. Add interfaces for real extension boundaries rather than every implementation class.
- Keep behavior services extensible. Prefer protected methods and dependencies over private members so application subclasses can reuse and specialize behavior. Provide protected factories or constructor injection where collaborators need to be replaced through the normal API.
- Reserve final classes for immutable configuration/data objects and stateless implementation utilities. Document extension points on behavior classes, and test that overrides work through public form-building entry points.
- Use readonly properties for fixed service dependencies rather than readonly service classes, so subclasses can maintain their own state.
- Declare property, parameter, and return types. Use constructor property promotion and readonly state where appropriate; keep mutable form-session state explicit.
- Explain each class's responsibility and its place in the form workflow in its docblock. Add concise examples for public extension points or behavior that is easier to understand concretely. Document nontrivial contracts. Give functions and methods meaningful docblocks as required by the CakePHP preset; document array shapes, generics, exceptions, and behavior that native types cannot express.
- Keep inline comments focused on otherwise unclear intent. Use imported class names in executable code.
- Use CakePHP translation functions for user-facing messages and routing APIs for generated URLs.
- Avoid unrelated refactors and dependencies. Document intentional public API changes and update usage examples with them.

## Form correctness and accessibility

- Escape text and attribute values by default. Require an explicit trusted-markup representation for raw HTML.
- Keep generated IDs stable and unique within the page. Associate labels, help text, and errors with the correct controls, without dangling ARIA references.
- Preserve the distinction between missing values, explicit null, false, zero, and empty strings.
- Test nested names, associations, choice groups, checkbox fallback values, disabled controls, multiple selections, and file inputs.
- Keep form sessions isolated. Preserve CSRF, method overrides, and tamper protection through custom renderers; do not unlock fields merely to bypass integration problems.
- Prefer native HTML semantics and progressive enhancement. Require no JavaScript runtime for ordinary form submissions.

## Tests and coverage

- Add meaningful behavior tests for features and regression tests for fixes. Test public contracts and observable output rather than private implementation details.
- Use PHPUnit and CakePHP testing facilities, including integration tests where framework interaction matters. Prefer real lightweight collaborators; use test doubles at external or expensive boundaries.
- Run the affected tests during development and the complete suite before handing off PHP changes.
- Maintain **strictly greater than 90% line coverage** across all production PHP under `src/`.
- Configure coverage to include unexecuted source files. Exclude dependencies, tests, and generated artifacts, never production code merely to increase the percentage.
- Generate a Clover report with Xdebug or PCOV. Add an explicit CI coverage gate that fails when the unrounded covered-line ratio is less than or equal to 0.90, the report is absent, or executable source has no coverage data. PHPUnit's report generation alone is not threshold enforcement.
- Cover failures, escaping, accessibility, and security-sensitive behavior even when the numerical target already passes. Never weaken assertions or use coverage-ignore annotations to satisfy the threshold.
- Treat a scaffold with no executable source as having no coverage result, not as passing the coverage requirement.

## Mago integration

Use Mago for static analysis, linting, and formatting. The CakePHP preset is maintained at <https://github.com/cakephp/mago-rules>; analyzer documentation is at <https://mago.carthage.software/latest/en/tools/analyzer/overview/>.

When bootstrapping the plugin, install the development dependencies:

```sh
composer require --dev cakephp/mago-rules carthage-software/mago
```

Start the root `mago.toml` with the upstream preset:

```toml
extends = "vendor/cakephp/mago-rules/cakephp.mago.toml"
```

- Configure the project's PHP version, owned source paths, tests, and dependency discovery using the installed Mago version's supported configuration.
- Let the preset register its extension worker. Do not copy its implementation or recreate its rules locally.
- Analyze production and test code, with dependency symbols available. Include PHP templates in formatting and linting; validate their runtime behavior with rendering tests.
- Keep Mago as the single formatting and linting authority. Do not add competing PHPCS/PHPCBF requirements by default.
- Start without analyzer or linter baselines. Do not generate or update baselines, disable rules, or add suppressions without explicit user authorization. Fix findings first.
- Review automated fixes and rerun tests after behavior-affecting changes.

Provide these Composer scripts when creating `composer.json`:

| Script | Command or responsibility |
| --- | --- |
| `test` | `phpunit` |
| `test-coverage` | Run PHPUnit with coverage and enforce the greater-than-90% gate |
| `analyze` | `mago analyze` |
| `lint` | `mago lint` |
| `format-check` | `mago format --check` |
| `format` | `mago format` |
| `lint-fix` | `mago lint --fix --format-after-fix --fail-on-remaining` |
| `rector` | `rector process --dry-run` |
| `rector-fix` | Apply Rector refactorings, then run Mago formatting |
| `check` | Run tests, analysis, linting, Rector, and format checks |

Composer scripts resolve dependency binaries from `vendor/bin`. For direct execution, use `vendor/bin/mago` and `vendor/bin/phpunit`.

## Rector integration

- Run Rector through `composer rector` for a dry run or `composer rector-fix` to apply refactorings and format the result with Mago.
- Keep Rector's PHP target aligned with Composer and review changes for public API and subclass compatibility.
- Mago rules take precedence. Preserve the approved Rector skips for CakePHP assertion style, explicit string casts, mixed-value annotations, fluent return contracts, and normalized template paths. Do not weaken Mago rules to accommodate Rector.
- Include the Rector dry run in `composer check` so local checks and CI enforce the same refactorings. Review automated changes and rerun the full suite and coverage before handing off.

## Documentation site

- Maintain the VitePress site in `docs/` and its configuration in `docs/.vitepress/`. Keep Node dependencies separate from the PHP runtime dependencies.
- Keep the README concise and link to the documentation site. The site owns the full explanation: show the problem and a control call connected to its PHP element before detailed setup, recipes, and reference material.
- Keep the homepage short, with a few tabbed code examples. Put conceptual explanations, recipes, and detailed contracts in the guides and reference pages.
- Prefer built-in navigation, local search, code groups, callouts, and details blocks over custom Vue components when they meet the need.
- Write CSS examples as multiline rule blocks with one declaration per line.
- Run `npm ci` and `npm run docs:build` for documentation-site changes. Fix broken links rather than disabling VitePress's link checks.
- Keep the repository Pages base path configured, and publish only from `main`. Pull requests must build the site without deploying it.
- Commit the npm lockfile; exclude installed dependencies, VitePress caches, and build output.

## Completion requirements

- CI must run `composer validate --strict`, `composer check`, and `composer test-coverage` with the required coverage driver enabled.
- Before completing a PHP change, run formatting, linting, analysis, relevant tests, and the full suite. Verify coverage after source or test changes.
- For documentation-only changes, check the content and examples; do not claim PHP checks were necessary or executed.
- Report actual checks, results, coverage, and any unavailable tooling. Never present skipped checks as passing.
- Do not commit dependencies, generated reports, caches, or credentials. Do not edit vendor code.
