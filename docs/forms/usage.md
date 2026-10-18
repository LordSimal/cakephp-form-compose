---
title: Fields and values
description: Configure controls, resolve values, render validation feedback, and build upload and search forms.
---

# Fields and values

Continue from [your first form](/getting-started). Examples that use `$form`
assume an open session unless they show the complete lifecycle. For the complete
option list, keep [Field options](/field-options) alongside this guide.

## Complete controls

A control includes the input and its surrounding presentation. Use `type` when
you want to override context inference:

```php
// Inside the article form.
echo $form->control('title', ['label' => 'Article title']);
echo $form->control('body', [
    'type' => 'textarea',
    'attributes' => ['rows' => 12],
]);
echo $form->control('published', ['type' => 'checkbox']);
```

Otherwise schema metadata supplies types: booleans become checkboxes, text becomes
a textarea, numbers become numeric inputs, and date/time columns get native date
or time inputs. Primary keys default to hidden fields. Explicit choice lists
infer a select. See [type inference](/field-options#type-inference) for the full mapping.

### Render several controls

`controls()` renders an ordered batch without an extra wrapper:

```php
echo $form->controls([
    'title',
    'body' => ['type' => 'textarea', 'attributes' => ['rows' => 12]],
    'published' => ['type' => 'checkbox'],
]);
```

Use application HTML around
separate calls when you need sections or grids.

### Opening and closing

Each binding owns its form lifecycle and input registry. Open once, render all
inputs, then close once. Rendered input IDs must be unique; explicit form IDs
must also be unique across the page. You can inspect a field before opening,
but inputs require an open form. See the [session reference](/form-reference#lifecycle-and-failures)
for invalid calls and retry behavior.

## Data and value sources

`bind()` accepts ORM entities, CakePHP `Form` objects, array contexts,
`ContextInterface` implementations, or `null`. It does not change those objects.
The default value lookup is:

1. An explicit field `value`, including explicit null.
2. Submitted request data.
3. The CakePHP context, including its schema defaults where enabled.
4. The field's `default` if no source supplies a value.

```php
// An explicit value overrides both the request and entity.
echo $form->control('title', ['value' => 'Preview title']);
```

This is useful for deliberate overrides; ordinary edit fields usually should use
the bound value. Request lookup distinguishes missing keys from null, false, zero,
and empty strings. A context's null result falls through to the next source or
default, following CakePHP context semantics. See the
[value options](/field-options#values) for `default` and `schemaDefault`.

### Search forms

Use query parameters before context values, and choose GET:

```php [templates/Articles/index.php]
<?php
$form = $this->Form->bind(null, [
    'type' => 'get',
    'valueSources' => ['query', 'context'],
    'url' => ['action' => 'index'],
]);
?>
<?= $form->open() ?>
<?= $form->control('q', ['type' => 'search', 'label' => 'Search articles']) ?>
<?= $form->submit('Search') ?>
<?= $form->close() ?>
```

The query parameter `q` repopulates the search input. `valueSources` belongs to
`bind()`; changing it in `open()` does not change field binding.

### Upload files

Enable multipart encoding on the **form**, then select a file **field**:

```php [templates/Articles/edit.php — upload variant]
<?php $form = $this->Form->bind($article, ['type' => 'file']); ?>
<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $form->control('attachment', [
    'type' => 'file',
    'attributes' => ['accept' => 'application/pdf'],
]) ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

Form `type => 'file'` selects multipart encoding and retains the context's default
POST/PUT method. You can instead set `enctype => 'multipart/form-data'` at binding
or opening. A file input in a non-multipart form throws `LogicException`.
For several files, add field `multiple => true`; the submitted name gains `[]`.
File and password controls never prefill their value.

## Labels, feedback, and escaping

Labels are generated from paths unless overridden. Help renders when configured;
errors come from the context and render when present. Ordinary text and attribute
values are escaped.

```php
echo $form->control('title', [
    'label' => 'Article title',
    'help' => 'Visible in article lists.',
    'partAttributes' => [
        'container' => ['class' => 'article-field'],
        'label' => ['class' => 'article-label'],
    ],
]);
```

Use `label => false`, `help => false`, or `errors => false` to suppress a part.
When suppressing labels, provide another accessible name through input attributes.
Disabling help or errors also removes their generated ARIA references. With a
custom layout, render the enabled feedback parts so those references have targets.

Trusted label, help, and button markup requires an explicit
[`Html` object](/template-reference#trusted-markup). Validation, authorization,
and saving remain in the application.

## Choose a template for one control

Use `template` to select a specific PHP element for a complete field:

```php
echo $form->control('title', ['template' => 'forms/article-title']);
```

This renders `templates/element/forms/article-title.php`. Continue with
[Use your own templates](/templates) for the matching PHP element, individual
part overrides, and custom presentation data.

For shared defaults, [create a theme](/themes). To offer named presentations
within a theme, [use variants](/variants). [How rendering works](/rendering)
explains how those choices fit together.

### Pass data to a template

Use `templateData` to supply custom values to the element and that field's parts.
These values stay in `$templateData`; they do not become HTML attributes. See the
[worked example](/templates#pass-custom-presentation-data) and
[scope reference](/template-reference#template-data).

## Association fields

Use CakePHP paths such as `author_id`, `tags._ids`, and `comments.0.body`.
[Association fields](/associations) explains choice discovery, nested records,
and validation feedback. For ordinary select and checkbox options, see
[Choice controls](/choices).

## Composing field parts

Use `$form->field()` when arranging a label, input, help, and errors independently.
Follow [Compose field parts](/field-parts) for a complete example and handwritten
input registration.

## Reusable forms

When form configuration repeats, extract a [blueprint](/blueprints).
[Reusable forms](/reusable-forms) explains when to keep configuration inline and
when to introduce a class. Both render through the controls and templates shown here.
