---
title: Migrate a core form
description: Move an existing CakePHP FormHelper form to FormCompose while retaining CakePHP validation and protection.
---

# Migrate a core form

Migrate one form at a time. Load FormCompose under the `Compose` alias alongside
core `Form` as shown in [Your first form](/getting-started#load-the-helper).
Keep your controller's authorization, `patchEntity()`, validation, and saving.

::: code-group

```php [Core FormHelper]
<?= $this->Form->create($article) ?>
<?= $this->Form->control('title', ['class' => 'title-input']) ?>
<?= $this->Form->control('author_id', ['options' => $authors, 'empty' => 'No author']) ?>
<?= $this->Form->submit('Save') ?>
<?= $this->Form->end() ?>
```

```php [FormCompose]
<?php $form = $this->Compose->bind($article); ?>
<?= $form->open() ?>
<?= $form->control('title', ['attributes' => ['class' => 'title-input']]) ?>
<?= $form->control('author_id', ['options' => $authors, 'empty' => 'No author']) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

:::

| Existing concern | FormCompose equivalent |
| --- | --- |
| `create($entity)` / `end()` | `bind($entity)` then session `open()` / `close()` |
| Inline HTML attributes on `control()` | Nested `attributes` for the input, `partAttributes` for other parts |
| String templates and widgets | Application-owned PHP elements through a `Theme`; custom submission semantics through `FieldTypeInterface` |
| `empty` select option | `empty` accepts a label, true, or false. Optional single selects automatically get a blank choice. |
| Validation feedback | The existing CakePHP context supplies errors and constraints; `help` and `errors` parts associate with the input |
| Reusable form configuration | Optional blueprints; templates still own layout |
| CSRF, method overrides, tamper protection | Keep CakePHP middleware and FormProtectionComponent enabled; input rendering registers submitted names |
| `postButton()` / `postLink()` | Helper shortcuts create independent protected forms; use `block` inside another form. See [POST actions](/post-actions). |

FormCompose does not forward arbitrary top-level field options as HTML attributes
or implement every core helper convenience method. Check the
[field option reference](/field-options) when porting options. For grids,
icons, or error wrappers, start with [a PHP theme](/themes), rather than porting
core widget subclasses or string-template substitutions.

Preserve configured names and IDs in custom markup. Render `$field->input()` to
retain submission registration, or follow the explicit
[handwritten input contract](/field-reference#register). Hiding a field in a
blueprint or layout controls presentation; authorize and validate submitted data
in the application as usual.

Custom layouts must render enabled help and error elements referenced by their
inputs. `close()` rejects missing generated feedback targets. For handwritten
feedback, use `$form->registerFeedback($field, 'help')` or `'errors'` after emitting
the element with its prepared attributes. To deliberately omit inline
descriptions while keeping summary errors, pass `['aria-describedby' => null]`
to `input()` and to `register()` for handwritten inputs.
