---
title: Form blueprints
description: Declare an article form and its PHP layout once, then render them with a short view call.
---

# Form blueprints

A blueprint owns a reusable set of fields and can select its own PHP layout.
Your page then needs only a rendering call. The controller supplies `$article`
and keeps its existing validation and saving workflow.

## Declare fields and a layout {#declare-and-use-a-blueprint}

<span id="keep-control-of-the-layout"></span>

Create the blueprint and element in the first two tabs, then use the third tab in
your article view. The plugin discovers the `article` alias from `#[Form('article')]`.

::: code-group

```php {17} [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use function Cake\I18n\__;

#[Form('article')]
class ArticleForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->template('forms/article');
        $form->add('title')->label(__('Article title'));
        $form->add('body')->type('textarea');
        $form->add('published')->type('checkbox');
    }
}
```

```php [templates/element/forms/article.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Blueprint\BlueprintView $form
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= $attributes ?>>
    <div class="article-content">
        <?= $form->controls(['title', 'body']) ?>
    </div>
    <aside class="publication-settings">
        <?= $form->control('published') ?>
    </aside>
</section>
```

```php [templates/Articles/edit.php]
<?php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->submit('Save article');
```

:::

The blueprint's `template()` call selects its layout. That element arranges the
fields; the view call adds the surrounding form and Save button. The element does
not open or close another form.

`$form` inside the element is a scoped blueprint view. It uses relative field
names, and each control still uses its own field templates.

Set the layout in `configure()` so the hook can choose it conditionally when
needed. Without a template setting, fields render in declaration order.

### Form and button configuration {#complete-form-shortcut}

Add `errorSummary()` to the rendering chain for an error summary, or
`withoutSubmit()` to omit the default button. Configure destination and form
attributes before output or `bind()`. See [complete-form settings](/form-reference#render).

## Choices and conditional fields {#configure-choices-and-conditional-fields}

Add a status field inside `ArticleForm::configure()` using the same fluent settings
as an ordinary control:

```php
$form->add('status')->choices([
    'draft' => __('Draft'),
    'review' => __('Ready for review'),
]);
```

The `__()` import is already in the class above. Add `<?= $form->control('status') ?>`
to the blueprint layout where the selector should appear.

To make the publication checkbox conditional, replace its `add()` statement with:

```php
if ($context->request->getAttribute('canPublish')) {
    $form->add('published')->type('checkbox');
}
```

Your application supplies `canPublish` as a request attribute. In the layout,
replace the publication aside with a guarded version:

```php
<?php if ($form->has('published')): ?>
    <aside class="publication-settings">
        <?= $form->control('published') ?>
    </aside>
<?php endif; ?>
```

Use `add()` to declare a field, `field()` to edit an existing declaration, and
`remove()` to remove one. See [blueprint authoring](/blueprint-reference#configure-and-blueprintbuilder)
for the complete hook API.

## Arrange the fields in your view instead {#blueprint-selection}

When only one screen needs a different arrangement, keep the blueprint's settings
but place the controls in that screen's view instead of rendering its layout:

`templates/Articles/edit.php`

```php [Arrange fields in the view]
<?php
$form = $this->Form
    ->form($article)
    ->blueprint('article')
    ->bind();
?>
<?= $form->open() ?>
<section class="article-content">
    <?= $form->controls(['title', 'body']) ?>
</section>
<?= $form->control('published') ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

The blueprint still supplies labels and types. Here the view owns opening,
closing, and arranging the fields; the selected blueprint layout is not rendered.
This example uses the original three fields. If you make a field conditional,
keep the same condition when placing it in a manual view.

### Aliases and class references

Application blueprints under `src/Form/` are discovered automatically. To select
a class explicitly instead of its alias:

```php
use App\Form\ArticleForm;

echo $this->Form
    ->form($article)
    ->blueprint(ArticleForm::class)
    ->submit('Save article');
```

<span id="translate-a-blueprint"></span>
<span id="inspect-values-and-field-configuration"></span>
<span id="read-request-data-in-a-layout"></span>
<span id="configuration-order"></span>

## Next steps {#application-services-and-discovery}

- [Combine or nest blueprints](/composition) for reusable sections and associations.
- [Repeat a row blueprint](/repeated-embeds) for collections such as comments.
- [Inject application services](/blueprint-configuration#inject-application-services) for dynamic choice lists.
- [Inspect layout values](/blueprint-reference#inspect-values-in-a-layout) and look up the [blueprint API](/blueprint-reference).
