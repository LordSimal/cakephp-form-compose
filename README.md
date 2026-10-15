# FormCompose

Composable form binding and native PHP rendering for CakePHP. The plugin provides
`FormCompose\View\Helper\FormHelper` alongside Cake's existing helper.

FormCompose separates resolved field state from HTML composition. Use complete
controls, individual parts, or handwritten inputs against the same context and
submission contract. The core never constructs or delegates to Cake's FormHelper.
Forms, buttons, native inputs, choices, and hidden infrastructure fields all render
through one PHP template system. It supplies plain semantic HTML, with no CSS
framework or JavaScript dependency. Application or plugin themes own styling and
structure.

## Requirements and setup

PHP 8.4+ and CakePHP 5.4+. CakePHP 6 development builds require PHP 8.5+.

This is an experimental plugin with an evolving API. To use this local checkout,
add it as a Composer path repository in a Cake application and require
`josbeir/cakephp-form-compose:@dev`. It has not been published to Packagist.

Register the plugin in your application's `bootstrap()` after `parent::bootstrap()`:

```php
use FormCompose\FormComposePlugin;

$this->addPlugin(FormComposePlugin::class);
```

Load the helper in `AppView::initialize()`:

```php
$this->loadHelper('Form', ['className' => 'FormCompose.Form']);
```

This opts into the new API for `$this->Form`. To keep Cake's existing helper in
the same view, load the new helper under an alias instead:

```php
$this->loadHelper('Compose', ['className' => 'FormCompose.Form']);
```

Existing FormHelper calls are not automatically translated to this API.

## Complete controls

```php
<?php $form = $this->Form->bind($article, ['url' => ['action' => 'edit', $article->id]]); ?>

<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $form->control('body', ['type' => 'textarea']) ?>
<?= $form->control('published', ['type' => 'checkbox']) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

Each binding creates an independent form session. FormCompose owns the lifecycle,
method overrides, and markup preparation. It uses Cake routing, request CSRF
metadata, and a focused integration with Cake's form-protection mechanism.
Configure Cake's normal CSRF middleware and FormProtectionComponent in the host
application as needed; the plugin does not enable them itself.

All inputs must be rendered before `close()`. Once final tokens are prepared,
additional input registration is rejected. Failed opening, closing, or button
templates leave the lifecycle and submission registry available for a safe retry.

Bind ORM entities, Cake `Form` objects, array contexts, existing `ContextInterface`
implementations, or `null`. Pass ORM context options through `['context' => [...]]`.
Configure a custom `ContextFactory` with the helper's `contextFactory` setting.

Native control inference uses context schema metadata and validation constraints.
Explicit `value` wins, including null. The default source order is submitted data,
then context. Use `['valueSources' => ['query', 'context']]` when binding search
forms. Request lookup distinguishes missing keys from explicit null, false, zero,
and empty strings. Context values follow the existing Cake context's semantics.

## Compose your own markup

```php
<?php
$email = $form->field('email', [
    'type' => 'email',
    'help' => 'Use your work address.',
]);
?>

<section class="contact-field">
    <?= $email->label(['class' => 'contact-label']) ?>
    <div class="input-with-adornment">
        <?= $this->element('icons/envelope') ?>
        <?= $email->input(['class' => 'contact-input', 'x-model' => 'email']) ?>
    </div>
    <?= $email->help() ?>
    <?= $email->errors() ?>
</section>
```

`$email->state` is an immutable snapshot exposing `path`, `name`, `id`, `type`,
`value`, `messages`, `constraints`, and `options`. Inspecting it, or calling
`attributes()`, never registers a submission. `input()` registers after successful
rendering. Render each input ID once; give repeated field presentations distinct
explicit IDs. Keep explicit form IDs unique across the page.

Set `help => false` or `errors => false` when omitting those parts so generated
ARIA references describe the intended presentation. Custom templates must preserve
these relationships. Radio and multi-checkbox controls use fieldsets and legends.

Text and attribute values are escaped. Use `new FormCompose\Rendering\Html(...)`
only for developer-controlled trusted label, help, or button markup.

For a completely handwritten native input, emit its resolved attributes and value,
then call `register()` once:

```php
<?php use FormCompose\Rendering\Html; ?>
<input type="email"<?= (string)$email->attributes() ?> value="<?= Html::text((string)$email->state->value) ?>">
<?php $email->register(); ?>
```

Handwritten markup must implement the field's native submission semantics. Use a
custom field type for a composite control or a non-native value representation.

## Themes belong to applications or plugins

```php
use FormCompose\Rendering\Theme;

$theme = new Theme(
    layers: ['forms/application', 'MyDesignSystem.forms'],
    defaults: [
        'container' => ['class' => 'field'],
        'input' => ['class' => 'control'],
        'checkbox.input' => ['class' => 'choice-control'],
        'label' => ['class' => 'caption'],
    ],
);

$form = $this->Form->bind($article, theme: $theme);
```

A layer is a Cake element prefix. In each layer, lookup tries
`variants/{variant}/{part}`, `{type}/{part}`, and `{part}`, in that order.
Layers are searched in their supplied order; the plugin's plain base elements are
last. Type-specific attribute defaults replace generic defaults; field and local
attributes then merge on top.

For example, `templates/element/forms/application/control.php` receives `$field`,
`$attributes`, and a `$parent` closure:

```php
<?php
/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<div<?= (string)$attributes ?>>
    <?= $field->label() ?>
    <div class="custom-input-shell"><?= $field->input() ?></div>
    <?= $field->errors() ?>
    <?= $field->help() ?>
</div>
```

Field parts are `control`, `input`, `label`, `help`, and `errors`; the container's
attribute bag is supplied to `control`. An input template calls
`$field->renderInput($attributes)` for its registered field type, or renders native
markup itself. Native controls delegate to `native` templates, with independent
`fallback`, `option`, `option-group`, `choice`, and `choice-group` parts.

`$parent()` delegates the same part to the next theme layer. An explicit field
override such as `['templates' => ['input' => 'forms/special_input']]` takes priority,
with `$parent()` delegating back to normal theme lookup.

The same lookup supports these additional scoped parts:

| Element relative to a theme layer | Purpose |
| --- | --- |
| `form/open.php` | Opening form tag and initial method/CSRF inputs |
| `form/close.php` | Final protection inputs and closing form tag |
| `form/hidden.php` | Each infrastructure hidden input |
| `button/control.php` | Submit, reset, or ordinary button content and structure |
| `textarea/native.php`, `select/native.php` | Native control structure |
| `option.php`, `choice.php` | Individual select options or radio/checkbox choices |

Form and button scopes also support type-specific and variant paths, such as
`form/patch/open.php` and `button/variants/primary/control.php`. Theme attribute
defaults use `form.open`, `form.close`, `form.hidden`, and `button`; choice defaults
use `option`, `choice`, `choice-label`, `choice-container`, and `choice-group`.

Form templates receive `$form`, prepared `$state`, and a `$hidden` list of attribute
bags. The opening template also receives `$attributes`. Render infrastructure with
`$form->hiddenFields($hidden, $state->presentation)`. Form token generation stays in
the lifecycle; templates must retain these infrastructure fields for valid requests.

Button templates receive `$label` and `$attributes`. They can include application
elements, icons, or wrappers just like field templates. For example, in
`templates/element/forms/application/button/control.php`:

```php
<?php
use FormCompose\Rendering\Html;
?>
<button<?= (string)$attributes ?>>
    <?= $this->element('icons/save') ?>
    <?= Html::text($label) ?>
</button>
```

Set per-form `templates` using `open`, `close`, or `hidden` keys when binding, and
per-button `templates` using the `control` key when calling `submit()`.

Use `variant` to select a named presentation. Use `attributes` for input attributes
and `partAttributes` for other parts. Attribute bags merge class tokens and ARIA ID
references; null removes an attribute and `replace()` replaces tokens explicitly.
They do not resolve conflicting CSS utilities. Control identity and behavior such
as `name`, `id`, `type`, `value`, and `secure` belong in field options rather than
HTML attribute bags.

The plugin bundles no framework themes. A theme for Tailwind, daisyUI, Bootstrap,
or another system can live entirely in the application or a separate plugin.
For Tailwind, write complete class names and include theme files in source scanning.

## Optional reusable definitions

```php
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;

$definition = new FormDefinition([
    FieldDefinition::make('name')->label('Display name'),
    FieldDefinition::make('email', 'email')->help('Use your work address.'),
    FieldDefinition::make('newsletter', 'checkbox')->label('Send updates'),
]);

$form = $this->Form->bind($user, definition: $definition);
echo $form->open(), $form->render(), $form->submit('Save'), $form->close();
```

Definitions are immutable and reusable. Fields remain individually accessible with
`field()` and `control()`; per-call options override definition options. Definitions
do not introduce a layout DSL, another validator, or persistence behavior.

## Custom controls and existing widgets

Implement `FieldTypeInterface` to render a custom control and return its submitted
names as `Submission` objects. Register it using
`bind($entity, ['types' => ['date-range' => $rangeType]])`, then request
`['type' => 'date-range']` on a field.

`render()` must return escaped markup without changing security state.
`submissions()` must describe exactly the successful inputs emitted, respecting
`disabled` and other submission behavior. Use `Submission($name, value: $value)`
for a hidden value that must remain locked. Explicit unlocking is available with
`locked: false`. Presentation templates cannot infer these semantics from HTML.

Existing Cake widgets are an explicit compatibility integration. Wrap an already
constructed widget with `FormCompose\Compatibility\WidgetType($widget, $context)`
and register it in the `types` map. The adapter honors
`WidgetInterface::secureFields()` without loading FormHelper or its widget locator.
Applications construct legacy widgets and their string templaters outside the core.
The old `widgets` and `widgetTemplates` factory options are rejected. Complex locked
values should use a custom field type. Existing FormHelper method overrides are
not adapted.

Custom renderers implement `RendererInterface::render(string $part, RenderNode $node)`.
A node exposes its `scope`, `type`, prepared `variables`, and presentation `options`.
Fields, forms, buttons, and hidden inputs all use this same contract. Native
rendering performs no binding or security mutations; the lifecycle commits state
only after the relevant rendering succeeds.

The `Security\FormProtection` integration directly uses Cake's `FormProtector`.
Cake marks this class internal, so compatibility tests against supported Cake
versions are required when upgrading that integration.

File controls require `['type' => 'file']` or multipart `enctype` when opening the
form. Dynamic collection protocols, reactive validation, and frontend state
synchronization are left to integrations.

## Development

```sh
composer install
composer check
composer test-coverage
```

Enable Xdebug coverage mode or PCOV before running coverage. Reports include all
production PHP, including unexecuted files. The CLI gate rejects coverage less than
or equal to 90%, missing reports, and reports with no executable source.

`composer check` runs PHPUnit, Mago analysis, linting, and format checks using the
upstream CakePHP Mago preset. `composer format` applies formatting; `composer lint-fix`
applies safe lint fixes. No analyzer or linter baselines are used.
