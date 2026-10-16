---
description: Resolve field values and compose controls with your own layouts.
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

### Render several controls

Use `controls()` for an ordered batch without an extra wrapper:

```php
echo $form->controls([
    'first_name',
    'last_name',
    'email' => ['type' => 'email', 'label' => 'Work email'],
]);
```

Indexed `FieldDefinition` objects are accepted alongside field names. Each entry
uses the same defaults, escaping, templates, and field extensions as `control()`.
The form must be open for nonempty batches. An empty batch returns an empty string.
The entire batch syntax is validated first; malformed entries throw
`InvalidArgumentException`. If rendering fails, input registrations from the batch
are rolled back so it can be retried. Previously rendered inputs remain registered.

For a complete form from a blueprint or definition, use the
[complete-form shortcut](./blueprints.md#complete-form-shortcut).

### Opening and closing

::: info Render inputs before closing
All inputs must be rendered before `close()`. Once final tokens are prepared,
additional input registration is rejected. Failed opening, closing, or button
templates leave the lifecycle and submission registry available for a safe retry.
:::

### Choose a template for one control

Set `template` to render a different PHP element for this call. For example, give
one email field its own layout without changing the form's theme:

::: code-group

```php [Control call]
echo $form->control('email', [
    'type' => 'email',
    'template' => 'forms/special-email',
    'templateData' => ['prefix' => '@'],
]);
```

```php [templates/element/forms/special-email.php]
<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array{prefix?: string} $templateData
 */
?>
<div<?= (string)$attributes ?>>
    <?= $field->label() ?>
    <div class="email-input">
        <span aria-hidden="true"><?= Html::text($templateData['prefix'] ?? '') ?></span>
        <?= $field->input() ?>
    </div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

:::

Use a CakePHP element name without the `.php` extension. The explicit element
takes priority over variants and theme layers. Its field parts still use the
normal templates, data binding, escaping, and submission protection.

Use plural `templates` when selecting individual parts. Both options can be
combined:

```php
echo $form->control('email', [
    'type' => 'email',
    'template' => 'forms/special-email',
    'templates' => ['input' => 'forms/email-input'],
]);
```

This also requires a `templates/element/forms/email-input.php` element for the
input part. `template` is shorthand for `templates['control']`; supplying different
values for both throws `InvalidArgumentException`. Matching values are accepted.

To customize every email field in a theme, use its `email/control.php` element
instead. See [creating a theme](./themes.md) and the
[template reference](./template-reference.md#explicit-overrides) for all part names
and parent-template behavior.

### Pass data to a template

`templateData` supplies a named array to the selected control element and all of
that field's parts, including parent templates. Every template receives the bag;
it defaults to `[]`. It works with theme elements as well as explicit overrides.

```php
echo $form->control('email', [
    'type' => 'email',
    'template' => 'forms/special-email',
    'templateData' => ['prefix' => '@', 'account' => $account],
]);
```

The element can read `$templateData['prefix']` or use the original account object
through `$templateData['account']`. Data keys stay inside the array and do not
replace prepared variables such as `$field` or become HTML attributes. Escape
text when outputting it, as the element above does with `Html::text()`.

Form and button elements accept their own `templateData`. See the
[template data reference](./template-reference.md#template-data) for scope,
replacement behavior, and supported values.

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

## Association fields

Use CakePHP's usual paths such as `author_id`, `tags._ids`, `details.summary`, and
`comments.0.body`. FormCompose discovers conventional choice lists, resolves
associated values and validation metadata, and keeps nested submission names.
See [association fields](./associations.md) for selects, checkbox groups, nested
records, junction data, and validation feedback.

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

Share field configuration across screens with an optional `FormDefinition`.
Render it wherever that collection belongs inside an open form:

```php
echo $form->control('company');
echo $form->definition($contact);
echo $form->control('newsletter', ['type' => 'checkbox']);
```

The [reusable definitions guide](./definitions.md) covers the builder, per-screen
overrides, conditional fields, and using individual definitions in custom layouts.
