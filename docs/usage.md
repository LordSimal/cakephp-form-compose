---
description: Resolve field values, compose controls, and reuse form definitions.
---

# Fields and form data

This guide assumes the [helper is loaded](./getting-started.md#load-the-helper).
Use `control()` for your theme's complete field layout, or `field()` to resolve an
object whose parts you can arrange yourself. Both follow the
[same rendering model](./rendering.md).

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

`control('title')` renders a `control.php` element with a bound field object.
The base element arranges its label, input, help, and errors. A
[theme](./themes.md) can replace that element to reuse your own layout.

Each binding creates a form session with its own inputs and submission state.
The session prepares form markup, method overrides, and security fields. It uses
CakePHP routing, request CSRF metadata, and CakePHP's form-protection mechanism.
Configure Cake's normal CSRF middleware and FormProtectionComponent in the host
application as needed; the plugin does not enable them itself.

### Opening and closing

::: info Render inputs before closing
All inputs must be rendered before `close()`. Once final tokens are prepared,
additional input registration is rejected. Failed opening, closing, or button
templates leave the lifecycle and submission registry available for a safe retry.
:::

## Data and value sources

Bind ORM entities, Cake `Form` objects, array contexts, existing `ContextInterface`
implementations, or `null`. Pass ORM context options through `['context' => [...]]`.
Configure a custom `ContextFactory` with the helper's `contextFactory` setting.

Native control inference uses context schema metadata and validation constraints.
Explicit `value` wins, including null. The default source order is submitted data,
then context. Use `['valueSources' => ['query', 'context']]` when binding search
forms. Request lookup distinguishes missing keys from explicit null, false, zero,
and empty strings. Context values follow the existing Cake context's semantics.

File controls require `['type' => 'file']` or multipart `enctype` when opening the
form.

## Composing field parts

The following replaces an email `control()` call inside an open form. This is
useful for a layout used only in this view; recurring layouts belong in a theme
element. The part methods still render their own PHP templates:

```php [templates/Users/edit.php]
<?php
$email = $form->field('email', [
    'type' => 'email',
    'help' => 'Use your work address.',
]);
?>

<section class="contact-field">
    <?= $email->label(['class' => 'contact-label']) ?>
    <div class="input-with-adornment">
        <span aria-hidden="true">@</span>
        <?= $email->input(['class' => 'contact-input', 'x-model' => 'email']) ?>
    </div>
    <?= $email->help() ?>
    <?= $email->errors() ?>
</section>
```

### Inspecting a field

`$email->state` is an immutable snapshot exposing `path`, `name`, `id`, `type`,
`value`, `messages`, `constraints`, and `options`. Inspecting it, or calling
`attributes()`, never registers a submission. `input()` registers after successful
rendering. Render each input ID once; give repeated field presentations distinct
explicit IDs. Keep explicit form IDs unique across the page.

### Labels, feedback, and escaping

Set `help => false` or `errors => false` when omitting those parts so generated
ARIA references describe the intended presentation. Custom templates must preserve
these relationships. Radio and multi-checkbox controls use fieldsets and legends.

::: tip Escaping
Text and attribute values are escaped. Use `new FormCompose\Rendering\Html(...)`
only for developer-controlled trusted label, help, or button markup.
:::

### Writing an input by hand

For a completely handwritten native input, emit its resolved attributes and value,
then call `register()` once:

```php
<?php use FormCompose\Rendering\Html; ?>
<input type="email"<?= (string)$email->attributes() ?> value="<?= Html::text((string)$email->state->value) ?>">
<?php $email->register(); ?>
```

Handwritten markup must implement the field's native submission semantics. Use a
custom field type for a composite control or a non-native value representation.

## Reusable definitions

```php{4-8}
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
describe fields; application services handle validation and persistence.
