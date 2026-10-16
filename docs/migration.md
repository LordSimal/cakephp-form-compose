---
title: Migrate a core form
description: Move an existing CakePHP FormHelper form to FormCompose while retaining CakePHP validation and protection.
---

# Migrate a core form

Migrate one form at a time. Load FormCompose under the `Compose` alias alongside
core `Form` as shown in [Your first form](./getting-started.md#load-the-helper).
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

FormCompose does not forward arbitrary top-level field options as HTML attributes
or implement every core helper convenience method. Check the
[field option reference](./field-options.md) when porting options. For grids,
icons, or error wrappers, start with [a PHP theme](./themes.md), rather than porting
core widget subclasses or string-template substitutions.

Preserve configured names and IDs in custom markup. Render `$field->input()` to
retain submission registration, or follow the explicit
[handwritten input contract](./field-reference.md#register). Hiding a field in a
blueprint or layout controls presentation; authorize and validate submitted data
in the application as usual.

## Earlier development definition API

The standalone fluent definition authoring API has been removed. Application
entry points accept field paths and options, or blueprint class names/aliases.

| Earlier API | Current API |
| --- | --- |
| `FieldDefinition::make('email', 'email')->label('Work email')` | `control('email', ['type' => 'email', 'label' => 'Work email'])` or `#[Field('email', type: 'email', label: 'Work email')]` |
| `FormDefinition::make()->add(...)` passed to binding or rendering | A `FormBlueprint` class passed by class name or discovered alias |
| Builder `add($fieldDefinition)` | `add('email', ['type' => 'email'])` |
| Builder `embed()` / `embedMany()` with a definition object | Pass a child blueprint class name or alias |
| `field($fieldDefinition)` or `control($fieldDefinition)` | Pass the field path and its options separately |
| `controls([$fieldDefinition])` | `controls(['email' => ['type' => 'email']])` |

Definition records remain internal to compilation and rendering. For compiled
children in a layout, continue using `embedded()` and `embeddedMany()`; these
preserve parent adjustments and do not run the child hook again.

Update field/control overrides on session, resolver, and layout subclasses to
accept `string $field`. Builder overrides now accept
`add(string $path, array $options = []): static`. Compiler and renderer factories
continue to receive internal configuration records through their extension points.
