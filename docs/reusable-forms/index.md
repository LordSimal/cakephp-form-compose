---
title: Reusable forms
description: Share field settings, markup, or an entire field collection without changing how forms bind data.
---

# Reusable forms

Start with ordinary controls. Extract the part that repeats:

| What repeats? | Use |
| --- | --- |
| One field's settings | A `FieldConfig`. |
| Markup and classes | A [PHP element](/templates) or [theme](/themes). |
| A set of fields | A [form blueprint](/blueprints). |

## Keep local configuration inline

Inside an open form:

```php
echo $form->control('email')
    ->type('email')
    ->required();
```

If several fields need those same settings, define a preset instead:

```php
use FormCompose\Configuration\FieldConfig;

$email = FieldConfig::builder()
    ->type('email')
    ->required();

echo $form->control('email', config: $email);
echo $form->control('billing.email', config: $email)->label('Billing email');
```

Configs are immutable: deriving a new config leaves the preset unchanged.
See [field configuration](/field-options#reuse-configuration) for named arguments
and combining presets.

## Extract a reusable form

A blueprint declares a collection once, then renders it wherever needed:

```php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->submit('Save article');
```

The [blueprint guide](/blueprints#declare-and-use-a-blueprint) defines `article` and
shows both automatic rendering and a manual view. Validation and saving continue
through your existing CakePHP application.

## Use blueprint defaults with a manual layout {#select-defaults-for-the-whole-binding}

Select the blueprint before `bind()`, then use the session's `open()`, `control()`,
and `close()` calls. Follow the [manual-view example](/blueprints#blueprint-selection).

For several independent sections, [combine blueprints](/composition#combine-blueprints).
For related records, use [nested blueprints](/composition#compose-an-association)
or [repeated rows](/repeated-embeds).
