---
title: Reusable forms
description: Choose inline controls for local configuration and blueprints for reusable forms.
---

# Reusable forms

Start with inline controls. Move recurring form configuration into a blueprint
when a named form, injected services, conditional fields, or embedded sections help.
Both use the same field options, PHP templates, binding, and submission protection.

## Choose the right layer

| Need | API |
| --- | --- |
| Configure a field where its markup belongs | `control('email', $options)` |
| Arrange a field's label, input, help, and errors | `field('email', $options)` and bound-field parts |
| Render several local controls | `controls(['title', 'body' => ['type' => 'textarea']])` |
| Package reusable form configuration | A `FormBlueprint` class |
| Add fields or choices for the current request | The blueprint's `configure()` hook |
| Arrange a reusable form or child section | A PHP blueprint layout |

## Keep local configuration inline

```php
$form = $this->Form->bind($article);
echo $form->open();
echo $form->control('title', [
    'label' => 'Article title',
    'help' => 'Displayed above the article.',
]);
echo $form->control('body', ['type' => 'textarea']);
echo $form->submit('Save article');
echo $form->close();
```

Use ordinary CakePHP elements to share markup, and ordinary PHP arrays or
application functions to share option values. A blueprint is optional.

## Extract a reusable form

```php [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;

#[Field('title', label: 'Article title', help: 'Displayed above the article.')]
#[Field('body', type: 'textarea')]
class ArticleForm extends FormBlueprint
{
}
```

## Render all declared fields

Use the helper's `render()` method to bind the article, render the blueprint's
fields and layout, add a button, and close the form:

```php
use App\Form\ArticleForm;

echo $this->Form->render(ArticleForm::class, $article, submit: 'Save article');
```

For explicit opening and closing, select the blueprint when binding and call
`definition()` inside the open form:

```php
use App\Form\ArticleForm;

$form = $this->Form->bind($article, definition: ArticleForm::class);
echo $form->open();
echo $form->definition(); // Render ArticleForm's fields through its layout.
echo $form->submit('Save article');
echo $form->close();
```

`bind()` prepares the form session and blueprint configuration; it emits no markup. The
`definition()` call above renders the selected blueprint. Without that call,
opening and closing the form would render no application fields.

## Use blueprint defaults with a manual layout {#select-defaults-for-the-whole-binding}

Select a blueprint at `bind()` when you want its field configuration but want to
arrange individual controls yourself. Each `control()` call renders only the
requested field:

```php
use App\Form\ArticleForm;

$form = $this->Form->bind($article, definition: ArticleForm::class);
echo $form->open();
echo $form->control('title', ['label' => 'Working title']);
echo $form->control('body');
echo $form->submit('Save article');
echo $form->close();
```

Here the two `control()` calls render the fields. The title inherits ArticleForm's
help text and overrides its label; the body inherits its textarea type. Any other
fields declared by the blueprint remain unrendered. The blueprint's collection
layout is also unused in this example.

Per-call options override defaults for that field call. Values and validation
metadata come from the bound article and request. Resolving a field with `field()`
emits no markup and registers no input; its rendering methods produce the parts.

Calling `definition(ArticleForm::class)` in an existing open session compiles and
renders that blueprint locally without installing defaults for later controls.
Calling `definition()` without an argument reuses the blueprint selected at
binding, without running its hook again. Render each control once: use the
blueprint layout or arrange its fields manually.

Continue with [Form blueprints](./blueprints.md) for runtime fields, choices, and
layouts, [Nested definitions](./composition.md) for child sections, and
[Repeated embeds](./repeated-embeds.md) for associated rows.
