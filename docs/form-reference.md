---
title: Helper and form session
description: Signatures, options, return values, and lifecycle contracts for the helper and a bound form session.
---

# Helper and form session

`$this->Form` is `FormCompose\View\Helper\FormHelper`. Its `bind()` method returns
a `FormCompose\FormSession`, called `$form` throughout these docs. These objects
have different jobs and methods. Follow [Your first form](./getting-started.md)
for a walkthrough, or jump directly to [field options](./field-options.md).

All rendering methods return HTML strings; they do not echo automatically.

## registerFeedback() {#register-feedback}

`$form->registerFeedback($field, 'help', $attributes = [])` registers a handwritten
help element after it is emitted with `$field->attributes('help', $attributes)`.
Use `'errors'` for handwritten errors. It requires an open session and reserves
the configured ID without rendering or registering submitted inputs. Do not also
render that element with `help()` or `errors()`.

Closing verifies generated input descriptions have rendered or registered targets.
A missing target throws `LogicException` and leaves the session open for correction.
Application-owned descriptions pointing to unrelated elements remain the
application's responsibility. See [field composition](./field-parts.md#preserve-feedback-and-accessible-names).

## postButton() and postLink() {#post-actions}

Helper shortcuts create independent protected action forms:

```php
echo $this->Form->postButton('Delete', ['action' => 'delete', $article->id], [
    'method' => 'delete',
    'data' => ['version' => $article->version],
]);
```

Both accept a label, URL, options, and optional named `theme` and `renderer`.
`postLink()` permits an omitted URL and requires JavaScript. `postButton()` is
native; optional `confirm` adds confirmation with a native dialog or an application
event handler. `block` buffers the
independent form outside a parent form. See [POST buttons and links](./post-actions.md)
for signatures, options, asset handling, and template contracts.

## bind() {#bind}

```php
public function bind(
    mixed $data = null,
    array $options = [],
    Theme|string|null $theme = null,
    ?RendererInterface $renderer = null,
    ?string $blueprint = null,
): FormSession;
```

Creates an isolated session. `$data` accepts an entity, CakePHP `Form`, array
context data, an existing `ContextInterface`, or null. It supplies metadata and
values; binding neither renders fields nor changes or saves the data.

```php
$form = $this->Form->bind($article, [
    'id' => 'article-editor',
    'url' => ['action' => 'edit', $article->id],
]);
```

`$theme` accepts null, `'Base'`, or a `FormCompose\Rendering\Theme` instance.
Null uses the helper's configured `theme`, which defaults to the base theme.
An explicit `Theme` replaces the configured theme; `'Base'` forces the bundled
base theme for this binding. Other strings fail. `$renderer` accepts a
`RendererInterface`; omitted, it uses `PhpRenderer`. `$blueprint` accepts a
blueprint class name or discovered alias. It installs field defaults without
rendering fields or their collection layout. After opening, call `blueprint()`
to render the selected blueprint, or call `control()` for each field you want to
arrange manually. Session `render()` renders the complete selected form.
See [blueprint defaults and manual layouts](./reusable-forms.md#select-defaults-for-the-whole-binding).

### Binding options

| Option | Type | Default and effect |
| --- | --- | --- |
| `id` | `string` | Generated `form-compose-N` across FormCompose helpers sharing the same Cake view. Also prefixes generated input IDs. Set explicit unique IDs when needed. |
| `context` | `array` | `[]`. Additional options passed to CakePHP's context factory; not used when `$data` is already a context. |
| `valueSources` | Nonempty list of `data`, `context`, `query` | `['data', 'context']`. Field value lookup order. |
| `passwordFields` | List of terminal field names | Inherits helper configuration, normally `['password', 'passwd']`. Replaces the names that infer password inputs, including nested paths. An empty list disables name-based inference; explicit password controls never prefill values. |
| `typeMap` | Map of schema names to control aliases | Inherits helper overrides. Binding entries override matching helper entries; native defaults remain for other schema types. |
| `types` | Map of aliases to `FieldTypeInterface` instances | `[]`. Explicit types override discovered types. |
| `errorSummary` | `bool` or summary options array | `false`. Opt-in presentation for complete-form `render()`; never emitted as a form attribute. |
| Form-opening options | See below | Stored for `open()`; local opening options replace matching keys. |

Form IDs are reserved at binding time for the owning Cake view.
Automatic IDs skip explicit reservations; duplicate explicit form IDs throw
`LogicException`. A separate Cake view starts an independent sequence. Form IDs
must be nonempty and contain no whitespace.

The binding ID is fixed at `bind()`. Changing `id` in `open()` does not change it.
Likewise context, types, type mappings, password field names, and value sources are binding settings, not opening
settings. See [Fields and values](./usage.md#data-and-value-sources).

### Helper configuration

Set these with `addHelper()`. Binding settings can also use the helper's
`setConfig()` before binding; `autoActionScript` is read during initialization:

| Setting | Value and default |
| --- | --- |
| `theme` | A `Theme` instance, `'Base'`, or null. Defaults to `'Base'`; null also selects base. Used when `bind()`, `postButton()`, or `postLink()` omits `theme:`; helper `render()` inherits it through binding. |
| `passwordFields` | Defaults to `['password', 'passwd']`. A list of terminal names, such as `['password', 'passwd', 'passphrase', 'password_confirmation']`. Each binding snapshots this list; its own `passwordFields` option replaces the helper default. Explicit field types and primary-key conventions take precedence. |
| `typeMap` | Additional schema-to-control mappings, default `[]`. Each binding snapshots helper overrides over the native defaults; its own entries override matching keys. Reset helper overrides with `setConfig('typeMap')`. |
| `autoActionScript` | Boolean, true by default. Registers the deferred `FormCompose.actions` asset in the `script` view block when the helper initializes, including pages with no initial actions. Render `$this->fetch('script')` in the layout. Set false before initialization to load or replace the behavior yourself. See [loading and dynamic fragments](./post-actions.md#load-the-enhancement). |
| `contextFactory` | A CakePHP `ContextFactory`; otherwise uses CakePHP's default providers. |
| `blueprintFactory` | A `BlueprintFactory` instance; otherwise creates a factory for dependency-free declarations. Invalid non-null instances fail. |

```php
// AppView::initialize(); $blueprints is an application-provided factory.
$this->addHelper('Form', [
    'className' => 'FormCompose.Form',
    'blueprintFactory' => $this->get('blueprints'),
]);
```

See [blueprint services](./blueprint-configuration.md#inject-application-services)
for supplying this view variable from the application container.

Password names can be configured without a helper subclass:

```php
$this->Form->setConfig('passwordFields', ['password', 'passwd', 'passphrase']);
$form = $this->Form->bind($account, [
    'passwordFields' => ['password', 'passwd', 'new_password'],
]);
```

Helper settings use CakePHP's normal array merging. To replace the helper list
instead of extending it, call `setConfig('passwordFields', $names, false)`.
Binding options always replace the inherited list.
Lists use exact, case-sensitive terminal names rather than full paths or patterns.
For example, `new_password` matches `account.new_password`. Changing helper
configuration affects future bindings; a binding override does not change the
helper default. Password-name configuration is never emitted as a form attribute.

See [Create a theme](./themes.md#select-theme) for configuring
an application theme in `AppView`. Helper configuration is local to that instance;
changing `theme` affects future bindings and action calls, while existing sessions
retain their selected theme. Per-call overrides never modify the configured default.

## open() {#open}

```php
public function open(array $attributes = []): string;
```

Renders the opening form tag and initial hidden inputs. Options replace matching
binding options; remaining HTML attributes apply to the form tag.

```php
$form = $this->Form->bind($article);
echo $form->open(['class' => 'article-editor', 'method' => 'patch']);
```

### Form-opening options

| Option | Type | Default and effect |
| --- | --- | --- |
| `url` | Route `array`, URL `string`, `false`, or `null` | Current request target. Route arrays/strings pass through CakePHP routing. `false` omits the action attribute. |
| `type` | `string` | `post` for new contexts, `put` for existing ones. `file` enables multipart encoding while retaining the context's default method. Otherwise selects the method unless `method` is supplied. |
| `method` | `string` | Overrides the method chosen through `type`. Accepts GET, POST, PUT, PATCH, DELETE, HEAD, or OPTIONS, case-insensitively. |
| `enctype` | `string` | Omitted, or `multipart/form-data` when `type` is `file`. |
| `encoding` | `string` | `App.encoding`, otherwise `UTF-8`; emitted as lowercase `accept-charset`. |
| `templates` | `array<string, string>` | Overrides `open`, `close`, or infrastructure `hidden` elements. |
| `variant` | `string` | Named variant for form templates; omitted by default. |
| `templateData` | `array<string, mixed>` | `[]`. Data for opening, closing, and infrastructure hidden-input templates. |
| Other HTML attributes | Attribute values | For example `class`, `novalidate`, and `data-*`, supplied directly in this array. |

GET renders `method="get"`; other effective methods render `method="post"`.
Methods other than GET/POST add `_method`. A request CSRF token is included for
non-GET forms. Final tamper-protection fields are rendered at `close()`.

Use plural `templates` for form elements. Singular `template` is a field/blueprint
shorthand, not a form-opening option. See [template reference](./template-reference.md#form-and-button-templates)
when customizing infrastructure markup.

## control() {#control}

```php
public function control(string $field, array $options = []): string;
```

Resolves a field and renders its complete presentation using its control element.
Hidden fields render only their input part. Requires an open session when its
input renders. Accepts every option in [Field options](./field-options.md).

```php
echo $form->control('title', [
    'label' => 'Article title',
    'attributes' => ['placeholder' => 'Enter a title'],
]);
```

A string is a context path, not a submitted bracket name. For example,
`comments.0.body` resolves nested metadata and submits `comments[0][body]`.
Per-call options override selected blueprint defaults.

## controls() {#controls}

```php
public function controls(array $fields): string;
```

Renders a batch in order, adding no wrapper. Accepted entries are indexed path
strings or `path => options` pairs.

```php
echo $form->controls([
    'title',
    'body' => ['type' => 'textarea'],
    'published' => ['type' => 'checkbox'],
]);
```

An empty array returns `''`; nonempty batches require an open form. Invalid
entries throw `InvalidArgumentException` before rendering begins. Failed batches
roll back their input registrations, preserving previously rendered inputs.

## field() {#field}

```php
public function field(string $field, array $options = []): BoundField;
```

Returns a resolved snapshot without HTML or input-registration side effects.
It may be called before opening. Uses the same [options](./field-options.md) and
configuration precedence as `control()`.

```php
$title = $form->field('title', ['help' => 'Displayed above the article.']);
// Inside an open form:
echo $title->label(), $title->input(), $title->help(), $title->errors();
```

See [Bound fields](./field-reference.md) for state and part methods.

## blueprint() {#blueprint}

```php
public function blueprint(
    ?string $blueprint = null,
    ?string $at = null,
    array $options = [],
): string;
```

Renders a collection inside the current open session, without opening/closing tags
or a submit button. With no argument, uses the collection selected at `bind()`;
without one, the default layout returns `''`.

```php
// The 'author' blueprint declares relative paths such as 'email'.
echo $form->blueprint('author', at: 'author');
```

Strings resolve a blueprint locally; they do not replace binding defaults. `at`
mounts relative field paths at a context path. `$options` are
[blueprint presentation options](./blueprint-reference.md#presentation-options),
not field options. Rendering shares the session's theme and submission registry.
See [layouts and composition](./composition.md) for examples.

## embedMany() {#embed-many}

```php
public function embedMany(
    string $path,
    string $blueprint,
    ?array $keys = null,
    array $options = [],
): string;
```

Render rows at an association path inside the open session. Null `keys` infers
original keys using binding value sources; `[]` renders no rows. Options select
the repeated wrapper and collection-owned error presentation. Use layout `embeddedMany()` to render already compiled rows while preserving
their hook results and parent adjustments.
See [Repeated embeds](./repeated-embeds.md) for layouts and blank-row selection.

## submit() {#submit}

```php
public function submit(string|Html $label, array $attributes = []): string;
```

Renders a themed button inside an open form. Ordinary labels are escaped; `Html`
labels are explicitly trusted. HTML attributes go directly in the second argument.

```php
echo $form->submit('Save article', [
    'class' => 'button-primary',
    'name' => 'intent',
    'value' => 'save',
]);
```

| Option | Default and behavior |
| --- | --- |
| `type` | `submit`. Use `button` or `reset` for other native button behavior. |
| `templates` | `[]`. The `control` key selects the button element. |
| `variant` | Omitted. Selects a button template variant. |
| `templateData` | `[]`. Application data scoped to the button element. |
| HTML attributes | For example `class`, `disabled`, `name`, and `value`; escaped on output. |

A named button is registered as an optional submission only after its template
succeeds. Use `templates['control']`, not the field-only `template` shorthand.

## close() {#close}

```php
public function close(): string;
```

Generates final tamper-protection inputs and the closing form markup. Takes no
arguments; form presentation is selected through binding/opening options. Call it
after every input and button. The session cannot reopen after closing.

## Complete-form render() {#render}

The helper creates a session; the session version uses an existing fresh binding:

::: code-group

```php [FormHelper::render()]
public function render(
    string $blueprint,
    mixed $data = null,
    array $options = [],
    string|Html|false|null $submit = null,
    array $submitOptions = [],
): string;
```

```php [FormSession::render()]
public function render(
    ?string $blueprint = null,
    array $options = [],
    string|Html|false|null $submit = null,
    array $submitOptions = [],
): string;
```

:::

Both open, render the blueprint, optionally render a button, and close. Null
`submit` uses the translated **Submit** label; false omits the button.
`submitOptions` uses the same options as `submit()`.

```php
echo $this->Form->render(
    'article',
    $article,
    options: ['url' => ['action' => 'edit', $article->id]],
    submit: 'Save article',
    submitOptions: ['class' => 'button-primary'],
);
```

Helper `options` go to `bind()`. Session `options` go to `open()`. Use binding
first when selecting a custom theme or renderer:

```php
use FormCompose\Rendering\Theme;

$theme = new Theme(layers: ['forms/application']);
$form = $this->Form->bind($article, theme: $theme, blueprint: 'article');
echo $form->render(submit: 'Save article');
```

The session's explicit blueprint stays local and does not replace defaults.
Without an explicit or selected blueprint, the body is empty. Use a fresh
binding to retry a failed complete-form rendering.

## errorSummary() {#error-summary}

```php
public function errorSummary(array $options = []): string;
```

Summarize eligible controls and repeated sections successfully rendered in this
open session. Returns `''` when none have errors. Render or buffer the body first;
field inspection does not register feedback. Summary rendering registers no inputs.

| Option | Default and behavior |
| --- | --- |
| `title` | Translated “Please correct the following errors.” Text or trusted `Html`. |
| `attributes` | Summary element attributes; default ID is the binding ID plus `-error-summary`. |
| `partAttributes['title']` | Heading attributes; default ID is the summary ID plus `-title`. |
| `template` / `templates['control']` | Explicit summary element; otherwise normal `error-summary/control` theme lookup. |
| `variant` | Named theme variant. |
| `templateData` | Custom data bag for the summary element. |

Complete-form `render()` supports `options: ['errorSummary' => true]` or an
options array in place of true. It renders the buffered controls after the summary.
Local false overrides binding opt-in. See [Error summaries](./error-summaries.md)
for scope, target selection, custom elements, and manual placement.

## hiddenFields() {#hidden-fields}

```php
public function hiddenFields(array $fields, array $presentation = []): string;
```

Template-facing utility for the prepared `list<Attributes>` of infrastructure
inputs. It renders them through `form/hidden.php`; it does not declare ordinary
application fields or register handwritten inputs.

```php
// In a form opening or closing element:
echo $form->hiddenFields($hidden, $state->presentation);
```

For an application hidden value, use `control('token', ['type' => 'hidden'])`.

## Lifecycle and failures

A session opens once. Inputs, buttons, and `blueprint()` require an open form;
`field()` and state inspection do not. Duplicate rendered input IDs and file
inputs outside multipart forms throw `LogicException`.

Opening, closing, and button template failures do not commit their failed step;
closing failure restores the open phase. A failed input template does not register
that input. Batch and blueprint rendering stage registrations and roll them back
on failure. Exceptions propagate to the application. Complete-form rendering does
not return partial HTML or automatically close after a failure; retry it with a
fresh session.
