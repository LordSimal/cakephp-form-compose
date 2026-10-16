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

Render it inside an existing open session:

```php
use App\Form\ArticleForm;

echo $form->definition(ArticleForm::class);
```

Or bind and render the complete form:

```php
use App\Form\ArticleForm;

echo $this->Form->render(ArticleForm::class, $article, submit: 'Save article');
```

## Select defaults without selecting a layout

Selecting a blueprint at `bind()` supplies defaults without rendering:

```php
use App\Form\ArticleForm;

$form = $this->Form->bind($article, definition: ArticleForm::class);
echo $form->open();
echo $form->control('title', ['label' => 'Working title']);
echo $form->control('body');
echo $form->submit('Save article');
echo $form->close();
```

Per-call options override blueprint defaults for that field call. Each resolution
uses the current form's values and validation metadata. No input is registered
until it renders.

`definition(ArticleForm::class)` renders locally without installing defaults for
later controls. With a blueprint selected at binding, `definition()` reuses its
compiled configuration without running its hook again.

Continue with [Form blueprints](./blueprints.md) for runtime fields, choices, and
layouts, [Nested definitions](./composition.md) for child sections, and
[Repeated embeds](./repeated-embeds.md) for associated rows.
