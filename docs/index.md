---
title: FormCompose for CakePHP
titleTemplate: false
layout: home

description: Build composable CakePHP forms with bound fields and application-owned PHP templates. Learn through practical examples, guides, and a complete API reference.

hero:
  name: FormCompose
  text: Composable CakePHP forms.
  tagline: Bind CakePHP data, render controls with PHP elements, and keep your application in charge of the HTML.
  image:
    src: /branding/formcompose-mark.svg
    alt: FormCompose logo
    width: 320
    height: 320
  actions:
    - theme: brand
      text: Build your first form
      link: /getting-started
    - theme: alt
      text: API reference
      link: /form-reference
---

## Simple forms and reusable blueprints

Start with bound controls and ordinary PHP views. Put custom field markup in a
PHP element, and extract a blueprint when the same fields repeat. Both approaches
use the same fluent configuration and rendering parts.

::: code-group

```php [Simple form]
<?php
$form = $this->Form->bind($article);
?>
<?= $form->open() ?>
<?= $form->control('title')->template('forms/article-title') ?>
<?= $form->control('body')->type('textarea') ?>
<?= $form->control('status')->choices([
    'draft' => 'Draft',
    'published' => 'Published',
]) ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

```php [Custom field markup]
<?php
// templates/element/forms/article-title.php

/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<div<?= (string)$attributes ?>>
    <?= $field->label() ?>
    <div class="title-input-shell"><?= $field->input() ?></div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

```php [Declare a blueprint]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use function Cake\I18n\__;

/**
 * Shares article fields across forms while keeping values and saving in CakePHP.
 */
#[Form('article')]
class ArticleForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title');
        $form->add('body')->type('textarea');
        $form->add('status')->choices([
            'draft' => __('Draft'),
            'published' => __('Published'),
        ]);
    }
}
```

```php [Render the blueprint]
<?php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->errorSummary()
    ->submit('Save article');
```

:::

Start with [your first form](/getting-started), choose [your own templates](/templates),
or share markup through [themes](/themes). For other tasks, see
[search forms](/search-forms), [file uploads](/file-uploads), and [custom controls](/custom-fields).

CakePHP supplies values, validation metadata, routing, and submission protection.
Ordinary forms need no CSS framework or JavaScript runtime.
