---
description: Signatures, options, return values, and lifecycle contracts for the helper and a bound form session.
---

# Helper and form session

`$this->Form` is `FormCompose\View\Helper\FormHelper`. Its `bind()` method returns
a `FormCompose\FormSession`, called `$form` throughout these docs. These objects
have different jobs and methods. Follow [Your first form](./getting-started.md)
for a walkthrough, or jump directly to [field options](./field-options.md).

All rendering methods return HTML strings; they do not echo automatically.

## bind() {#bind}

```php
public function bind(
    mixed $data = null,
    array $options = [],
    Theme|string|null $theme = null,
    ?RendererInterface $renderer = null,
    FormDefinition|string|null $definition = null,
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
Other strings fail. `$renderer` accepts a `RendererInterface`; omitted, it uses
`PhpRenderer`. `$definition` accepts a `FormDefinition`, blueprint class name, or
discovered alias. It installs field defaults without rendering a collection.

### Binding options

| Option | Type | Default and effect |
| --- | --- | --- |
| `id` | `string` | Generated `form-compose-N` within the helper. Also prefixes generated input IDs. Set explicit unique IDs when needed. |
| `context` | `array` | `[]`. Additional options passed to CakePHP's context factory; not used when `$data` is already a context. |
| `valueSources` | Nonempty list of `data`, `context`, `query` | `['data', 'context']`. Field value lookup order. |
| `types` | Map of aliases to `FieldTypeInterface` instances | `[]`. Explicit types override discovered types. |
| Form-opening options | See below | Stored for `open()`; local opening options replace matching keys. |

The binding ID is fixed at `bind()`. Changing `id` in `open()` does not change it.
Likewise context, types, and value sources are binding settings, not opening
settings. See [Fields and values](./usage.md#data-and-value-sources).

### Helper configuration

Set these with `addHelper()` or the helper's `setConfig()` before binding:

| Setting | Value and default |
| --- | --- |
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

Use plural `templates` for form elements. Singular `template` is a field/definition
shorthand, not a form-opening option. See [template reference](./template-reference.md#form-and-button-templates)
when customizing infrastructure markup.

## control() {#control}

```php
public function control(string|FieldDefinition $field, array $options = []): string;
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
Explicit field definitions and per-call options override selected binding defaults.

## controls() {#controls}

```php
public function controls(array $fields): string;
```

Renders a batch in order, adding no wrapper. Accepted entries are indexed path
strings, indexed `FieldDefinition` objects, or `path => options` pairs.

```php
use FormCompose\Field\FieldDefinition;

echo $form->controls([
    'title',
    'body' => ['type' => 'textarea'],
    FieldDefinition::make('published', 'checkbox'),
]);
```

An empty array returns `''`; nonempty batches require an open form. Invalid
entries throw `InvalidArgumentException` before rendering begins. Failed batches
roll back their input registrations, preserving previously rendered inputs.

## field() {#field}

```php
public function field(string|FieldDefinition $field, array $options = []): BoundField;
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

## definition() {#definition}

```php
public function definition(
    FormDefinition|string|null $definition = null,
    ?string $at = null,
    array $options = [],
): string;
```

Renders a collection inside the current open session, without opening/closing tags
or a submit button. With no argument, uses the collection selected at `bind()`;
without one, the default layout returns `''`.

```php
use App\Form\AuthorForm;

// AuthorForm declares paths relative to an author, such as 'email'.
echo $form->definition(AuthorForm::class, at: 'author');
```

Strings resolve a blueprint locally; they do not replace binding defaults. `at`
mounts relative field paths at a context path. `$options` are
[definition presentation options](./definition-reference.md#presentation-options),
not field options. Rendering shares the session's theme and submission registry.
See [layouts and composition](./composition.md) for examples.

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
    FormDefinition|string $definition,
    mixed $data = null,
    array $options = [],
    string|Html|false|null $submit = null,
    array $submitOptions = [],
): string;
```

```php [FormSession::render()]
public function render(
    FormDefinition|string|null $definition = null,
    array $options = [],
    string|Html|false|null $submit = null,
    array $submitOptions = [],
): string;
```

:::

Both open, render the definition, optionally render a button, and close. Null
`submit` uses the translated **Submit** label; false omits the button.
`submitOptions` uses the same options as `submit()`.

```php
use App\Form\ArticleForm;

echo $this->Form->render(
    ArticleForm::class,
    $article,
    options: ['url' => ['action' => 'edit', $article->id]],
    submit: 'Save article',
    submitOptions: ['class' => 'button-primary'],
);
```

Helper `options` go to `bind()`. Session `options` go to `open()`. Use binding
first when selecting a custom theme or renderer:

```php
use App\Form\ArticleForm;
use FormCompose\Rendering\Theme;

$theme = new Theme(layers: ['forms/application']);
$form = $this->Form->bind($article, theme: $theme, definition: ArticleForm::class);
echo $form->render(submit: 'Save article');
```

The session's explicit definition stays local and does not replace defaults.
Without an explicit or selected definition, the body is empty. Use a fresh
binding to retry a failed complete-form rendering.

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

A session opens once. Inputs, buttons, and `definition()` require an open form;
`field()` and state inspection do not. Duplicate rendered input IDs and file
inputs outside multipart forms throw `LogicException`.

Opening, closing, and button template failures do not commit their failed step;
closing failure restores the open phase. A failed input template does not register
that input. Batch and definition rendering stage registrations and roll them back
on failure. Exceptions propagate to the application. Complete-form rendering does
not return partial HTML or automatically close after a failure; retry it with a
fresh session.
