---
title: Helper and form session
description: Typed fluent form setup, binding, rendering, and submission lifecycle contracts.
---

# Helper and form session

`FormHelper::form()` creates a mutable builder. `bind()` captures its configuration
in an isolated `FormSession`. Rendering uses the session's input registry and
CakePHP protection metadata.

## form() and bind() {#bind}

```php
use FormCompose\Configuration\FormConfig;

$form = $this->Form
    ->form($article, config: new FormConfig(method: 'patch'))
    ->url(['action' => 'edit', $article->id])
    ->attributes(['class' => 'article-form'])
    ->bind();
```

`form(mixed $data = null, FormConfig|null $config = null): FormBuilder` accepts ORM
entities, CakePHP `Form` objects, array contexts, `ContextInterface`, or null.
`bind(): FormSession` creates a fresh session each time; later builder changes do
not change existing sessions. There is no helper-level `bind()` method.

### Binding options

These settings are available on both `FormBuilder` and immutable `FormConfig`
instances, including those created by `FormConfig::builder()`:

| Method | Contract |
| --- | --- |
| `url(array\|string\|false\|null)` | Cake route or URL; null uses the request URL, false omits the action. |
| `method(?string)` | GET, POST, PUT, PATCH, or DELETE. Null uses context defaults. Non-native methods use a POST method override. |
| `id(?string)` | Explicit page-unique form ID; null generates one. |
| `multipart(bool = true)` | Enable multipart uploads without changing the HTTP method. |
| `enctype(?string)`, `encoding(?string)` | Form encoding and accepted character set. |
| `valueSources(array)` | Ordered list of `data`, `query`, and `context`; default `['data', 'context']`. |
| `context(array)` | CakePHP context-provider options. |
| `passwordFields(array)` | Replace the terminal password field names; default `['password', 'passwd']`. |
| `typeMap(array)` | Override schema-type to control-type mappings. |
| `types(array)` | Register aliases mapped to `FieldTypeInterface` instances. |
| `blueprint(?string)` | Append reusable field defaults and body declarations by alias or class. Calls accumulate in order; null clears the selection. |
| `theme(Theme\|null)`, `renderer(?RendererInterface)` | Per-form rendering collaborators. |
| `errorSummary(bool\|SummaryConfig = true)` | Configure the complete form's summary. |
| `submit(string\|Html\|false\|null, ?ButtonConfig)` | Configure the complete form's action; null uses translated Submit. |
| `withoutSubmit()` | Omit that action. |
| `submitConfig(ButtonConfig)` | Configure its presentation and native button state. |

On `FormBuilder`, setters update that builder and `apply(FormConfig)` overlays
explicit settings. On `FormConfig`, fluent methods return new configs and
`merge(FormConfig)` combines explicit settings. `FormConfig::builder()` returns
`FormConfig` itself; pass it directly wherever a config is accepted. Common settings
also have named constructor arguments: `new FormConfig(url: ..., method: ..., id: ...)`.
`FormConfig::blueprints` exposes the ordered list of selected aliases or classes.
When a config explicitly supplies a blueprint selection, `apply()` and `merge()`
replace the whole selection; an unspecified selection preserves existing defaults.
The shared [presentation API](/field-options#templates) configures attributes,
templates, variants, and template data.

### Helper configuration

CakePHP helper loading still uses CakePHP's own configuration array:

```php
$this->addHelper('Form', [
    'className' => 'FormCompose.Form',
    'theme' => $theme,
    'blueprintFactory' => $blueprints,
]);
```

The helper also accepts `passwordFields`, `typeMap`, `contextFactory`,
`themeDebug`, and `autoActionScript`. Use a form builder for `valueSources`,
custom type instances, and a renderer. Per-form settings override helper defaults.
See [blueprint services](/blueprint-configuration) for dependency injection.

## open() {#open}

```php
echo $form->open();
```

`open(): string` renders the form tag, CSRF field where supplied by CakePHP,
and method override. Configure it **before** `bind()`; `open()` accepts no options.
Existing entities default to PUT, new entities to POST. GET uses native query submission.
A file input requires a multipart form.

### Form-opening options

Use the form builder's `url()`, `method()`, `id()`, `multipart()`, `enctype()`,
`encoding()`, and `attributes()` methods. Select opening/closing elements with
`partTemplate('open', ...)` and `partTemplate('close', ...)`. `template()` selects
the complete form's `layout` element.

## control() {#control}

```php
echo $form->control('email')
    ->label('Email address')
    ->required()
    ->attributes(['autocomplete' => 'email']);
```

`control(string $field, FieldConfig|null $config = null): ControlBuilder` configures
a complete control. Echo or `render(): string` resolves its final settings and
renders it. Creating or configuring a control renders nothing. See
[field configuration](/field-options) for every setter.

## controls() {#controls}

```php
echo $form->controls([
    'title',
    $form->control('body')
        ->type('textarea')
        ->attributes(['rows' => 12]),
    $form->control('published')->type('checkbox'),
]);
```

Accepts an iterable of paths, `FieldSpec` objects, or `ControlBuilder` objects
from this session. It validates declarations and ownership before rendering.
The batch has no wrapper and rolls back registrations if any control fails.
Use `new FieldSpec('email', $emailConfig)` for reusable configured lists.

## field() {#field}

```php
$title = $form->field('title')
    ->help('Visible in article lists.')
    ->resolve();
$currentTitle = $title->state->value;
```

`field(string $field, FieldConfig|null $config = null): FieldBuilder` configures a
field; `resolve(): BoundField` captures immutable state. Resolution, state reads,
and attribute inspection render nothing and register no inputs. Use
[bound field parts](/field-reference) to arrange custom HTML.

## blueprint() {#blueprint}

```php
echo $form->blueprint('article')->template('forms/article');
```

`blueprint(?string $blueprint = null, ?string $at = null): PartBuilder` renders
a collection inside an open form. Null renders all of the binding's selected
blueprints in order, retaining their individual layouts. The composition shares
one input-registration transaction, so a later layout failure rolls back earlier
sections too.
An explicit selection stays local to this occurrence. `at: 'author'` scopes it
to that context path. Layout presentation uses the shared fluent methods.
With several selected blueprints, presentation overrides apply to each one.
A shared occurrence `id` is rejected before rendering; use a surrounding wrapper
or configure separate blueprint IDs. A single selection or explicit local
occurrence still accepts `attributes(['id' => 'details'])`.

## embedMany() {#embed-many}

```php
echo $form->embedMany('comments', 'comment')
    ->keys([0, 2])
    ->label('Comments');
```

`embedMany(string $path, string $blueprint, EmbedConfig|null $config = null): RepeatedEmbedBuilder`
compiles and renders repeated rows at that path. Null keys infer bound keys;
`[]` renders no rows. See [repeated embeds](/repeated-embeds).

## submit() {#submit}

```php
echo $form->submit('Save')
    ->name('intent')
    ->value('save')
    ->attributes(['class' => 'button']);
```

`submit(string|Html $label, ButtonConfig|null $config = null): ButtonBuilder` creates
a renderable button. Setters include `type(string)`, `name(?string)`,
`value(mixed)`, `disabled(bool = true)`, and common presentation methods.
Named enabled buttons participate in the submission contract after successful
rendering. Ordinary labels are escaped; trusted markup requires `Html`.

## close() {#close}

`close(): string` validates rendered identities and feedback targets, renders
final tamper-protection fields, and closes the form. Inputs may only render while
it is open. Configure CakePHP's CSRF middleware and FormProtectionComponent in
the application; loading this helper does not enable them.

## Complete-form render() {#render}

```php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->errorSummary()
    ->submit('Save article');
```

`FormBuilder::render()` binds a fresh session and renders the whole form.
Echoing the builder has the same effect. `FormSession::render(): string` opens
that existing session, buffers its selected blueprints, renders the summary and
layout, then closes it. Configure complete rendering before binding; session
`render()` accepts no settings.
Chain `blueprint('article')->blueprint('article_more')` before binding to compose
several blueprints. Their field defaults are combined, and duplicate field or
embed paths fail before the form opens. See [blueprint composition](/composition#combine-blueprints).

### Application-owned complete-form layouts

`template('forms/editor')` on the form builder selects the complete form layout.
Its variables are `$form`, `$state`, `$body`, `$summary`, `$submit`, `$submitConfig`,
`$attributes`, and `$templateData`. The opening tag has already rendered; emit
the buffered body once and do not open or close another form.

```php
<?php

/**
 * @var \FormCompose\Form\FormSession $form
 * @var \FormCompose\Configuration\ButtonConfig $submitConfig
 */
?>
<?= $summary ?>
<section<?= (string)$attributes ?>><?= $body ?></section>
<?php if ($submit !== false): ?>
    <?= $form->submit($submit, config: $submitConfig) ?>
<?php endif;
```

## errorSummary() {#error-summary}

```php
echo $form->errorSummary()
    ->title('Please review these fields')
    ->attributes(['class' => 'errors']);
```

`errorSummary(SummaryConfig|null $config = null): SummaryBuilder` renders a summary
of errors from successfully rendered controls. Render it after the controls;
buffer their HTML first if the summary should appear above them. Inspection
alone adds no summary entries. `errors(false)` suppresses that field's entry.
See [error summaries](/error-summaries).

## hiddenFields() {#hidden-fields}

`hiddenFields(array $fields, PresentationConfig $presentation = new PresentationConfig()): string`
renders internal metadata name/value pairs through the shared hidden element.
For application fields, set `type('hidden')` on a `control('record_id')` builder so binding,
registration, and protection use the normal field workflow.

## registerFeedback() {#register-feedback}

`registerFeedback(BoundField $field, string $part, array $attributes = []): void`
registers a handwritten help or errors element after you emit it. It reserves
its configured ID and participates in rollback without registering submissions.
See [handwritten feedback](/field-parts#preserve-feedback-and-accessible-names).

## postButton() and postLink() {#post-actions}

`postLink()` returns `ActionBuilder`; `postButton()` returns `PostButtonBuilder`,
which also exposes native button `name()`, `value()`, and `disabled()` setters.
Both offer `method()`, `data()`, `confirm()`, `block()`, `form(FormConfig)`,
and common presentation setters.
`block()` takes a nonempty block name or null. See [POST actions](/post-actions)
for delegated JavaScript, blocks, and protection contracts.

## Lifecycle and failures

Open a session once, render inputs, close once. Repeated opening or closing,
rendering inputs before opening or after closing, and reusing rendered IDs fail
with `LogicException`. Renderable builders do not cache HTML; rendering one twice
attempts to render it twice. Do not use both a whole control and its input.

Failed control, batch, and blueprint renders roll back pending registrations.
A failed close leaves the session open, allowing missing feedback to be emitted
before retrying. Exceptions propagate; configuration and inspection never unlock
fields to bypass protection.
