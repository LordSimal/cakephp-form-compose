---
title: Field definitions
description: Understand reusable field configuration, share collections across forms, and choose when a blueprint helps.
---

# Field definitions

Start with inline `control()` calls. When the same labels, choices, or help text
repeat across screens, extract that configuration. Values and validation still
come from whichever entity or context you bind.

## Choose the right layer

| Object | Responsibility | Example use |
| --- | --- | --- |
| `FieldDefinition` | Immutable configuration for one field | Reuse the title's label and help text. |
| `FormDefinition` | Immutable ordered collection of fields and embeds | Share an article's editable fields across screens. |
| `FormBlueprint` | PHP class compiled into a `FormDefinition` | Declare fields with attributes and configure them for the current request. |
| PHP definition layout | Markup that arranges a collection's contents | Put title and summary in a grid, then render an author section. |

A blueprint and an ordinary definition use the same field options and rendering
pipeline. Neither is required for ordinary forms. A definition layout arranges
several fields; a control template arranges the parts of **one** field.

## Reuse one field

This definition stores the options you would otherwise pass to `control()`:

```php
use FormCompose\Field\FieldDefinition;

$title = FieldDefinition::make('title')
    ->label('Article title')
    ->help('Displayed above the article.');

// Inside an open form bound to $article:
echo $form->control($title);
```

For manual parts, use `$form->field($title)` to obtain a bound field. The
`FieldDefinition` itself contains no bound value, HTML, or submission state.

## Build once, render where needed

Group related fields into an immutable collection:

```php
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;

$articleFields = FormDefinition::make()
    ->add(FieldDefinition::make('title')->label('Article title'))
    ->add(FieldDefinition::make('body', 'textarea'));

$form = $this->Form->bind($article);
echo $form->open();
echo $form->definition($articleFields);
echo $form->control('published', ['type' => 'checkbox']);
echo $form->submit('Save article');
echo $form->close();
```

The collection renders where `definition()` is called. The bundled layout adds
no wrapper, opening/closing tags, or button. You can place application HTML and
additional controls before or after it. Each rendering uses the current form's
context, theme, and protection lifecycle.

Construct reusable configuration in application code and pass it to the view as
you would other view data. No particular service or controller lifecycle is required.

## Customize without changing the shared definition

Every change returns a new collection. Keep that return value:

```php
$editingFields = $articleFields->with('title', [
    'help' => 'Changing the title also changes how this article appears in lists.',
]);
```

`$articleFields` remains unchanged. `with()` preserves field order and replaces
matching top-level option keys. Nested arrays such as `attributes` and
`templateData` are replaced as whole values. The full
[configuration contract](./definition-reference.md#formdefinition) lives in the reference.

For optional fields, assemble a collection before rendering:

```php
use FormCompose\Field\FieldDefinition;

// $canPublish is an application permission decision.
$editingFields = $articleFields;
if ($canPublish) {
    $editingFields = $editingFields->add(
        FieldDefinition::make('published', 'checkbox')->label('Publish article'),
    );
}
```

Removing or omitting a field controls presentation. Authorize submitted changes
in the application independently.

## Select defaults for the whole binding

There are two ways to use a collection. They have different effects:

::: code-group

```php [Render here]
$form = $this->Form->bind($article);
echo $form->open();
echo $form->definition($articleFields);
echo $form->submit('Save article');
echo $form->close();
```

```php [Use as field defaults]
$form = $this->Form->bind($article, definition: $articleFields);
echo $form->open();
echo $form->control('title'); // Inherits Article title.
echo $form->control('body');  // Inherits textarea.
echo $form->submit('Save article');
echo $form->close();
```

:::

`definition($articleFields)` renders locally; it does not install defaults for
later independent field calls. `bind(..., definition: $articleFields)` selects
defaults without rendering. A subsequent `$form->definition()` renders that
already selected collection.

To reuse just one field from a collection without selecting binding defaults,
pass its configuration explicitly:

```php
// Inside an open form; render this instead of the collection's title control.
echo $form->control($articleFields->field('title'), ['label' => 'Working title']);
```

Per-call options override collection defaults. Avoid rendering the same input twice.

## Render a complete form

When the sequential layout is sufficient, use the helper shortcut:

```php
echo $this->Form->render($articleFields, $article, submit: 'Save article');
```

This binds, opens, renders the collection, adds a button, and closes. See
[render()](./form-reference.md#render) for form options, button options, custom
themes, and omitting the button.

## Add a class or a reusable layout

Move recurring declarations into a [form blueprint](./blueprints.md) when a named
PHP class and request-aware configuration are useful. Use
[nested definitions and layouts](./composition.md) to select a PHP layout or embed
a relative child collection. The [definitions reference](./definition-reference.md)
contains all collection methods and declaration options.
