---
title: Choice controls
description: Render selects, checkboxes, radio buttons, grouped options, and choices from backed enums.
---

# Choice controls

Give `choices()` a map of submitted values to labels. The bound field value
selects the matching option, so an edit form normally needs no `value()` call.
Examples below go inside an [open form](/usage#render-fields).

## A select list

```php
echo $form->control('status')
    ->choices([
        'draft' => 'Draft',
        'published' => 'Published',
    ])
    ->placeholder('Choose a status');
```

`choices()` selects a select control automatically. Choosing “Published” submits
`status=published`. `placeholder()` labels its empty option; use
`placeholder(false)` to omit it.

## A single checkbox

```php
echo $form->control('published')
    ->type('checkbox')
    ->label('Publish this article');
```

This submits `'1'` when checked and `'0'` when unchecked. A bound true or 1 checks
it automatically. For application-specific values:

```php
echo $form->control('approval')
    ->type('checkbox')
    ->checkedValue('approved')
    ->uncheckedValue('pending');
```

## Radio buttons

```php
echo $form->control('visibility')
    ->type('radio')
    ->label('Who can read this article?')
    ->choices([
        'public' => 'Everyone',
        'private' => 'Editors only',
    ]);
```

The group uses a fieldset and legend. Each option has its own label.

## Multiple selections

Choose one presentation for the same set of topics:

::: code-group

```php [Multiple select]
echo $form->control('topics')
    ->multiple()
    ->choices([
        'php' => 'PHP',
        'testing' => 'Testing',
    ]);
```

```php [Checkbox group]
echo $form->control('topics')
    ->type('multicheckbox')
    ->choices([
        'php' => 'PHP',
        'testing' => 'Testing',
    ]);
```

:::

Both submit `topics[]`. A bound array such as `['php', 'testing']` selects both.
For related ORM records, use [association selectors](/associations), such as
`tags._ids`.

## Backed enums

If your application already uses an enum, pass its class to `choices()`. This
example defines the enum in the first tab and uses it in the second.

::: code-group

```php [src/Enum/Status.php]
<?php
declare(strict_types=1);

namespace App\Enum;

use Cake\Database\Type\EnumLabelInterface;
use function Cake\I18n\__;

enum Status: string implements EnumLabelInterface
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => __('Published'),
        };
    }
}
```

```php [Inside an open form]
use App\Enum\Status;

echo $form->control('status')->choices(Status::class);
```

:::

Cases submit their backing values. `EnumLabelInterface` supplies translated labels;
without it, labels use humanized case names. Pass `[Status::Draft]` instead of the
class when you want only a subset of cases.

## Groups and attributes on individual choices

For ordinary option groups, use nested maps:

```php
echo $form->control('audience')->choices([
    'Public' => [
        'everyone' => 'Everyone',
        'subscribers' => 'Subscribers',
    ],
    'Internal' => [
        'staff' => 'Staff',
    ],
]);
```

When one option needs attributes, use `ChoiceConfig` for that option:

```php
use FormCompose\Configuration\ChoiceConfig;

echo $form->control('audience')->choices([
    'staff' => 'Staff',
    ChoiceConfig::builder('legacy', 'Legacy team')->disabled(),
]);
```

See [choice configuration](/field-options#choices-and-fallbacks) for group metadata,
enum discovery, and all choice setters.

## Empty selections and disabled controls

Clearing a multiple selection still submits an empty value, so previously selected
items are not restored. Checkboxes and other choice controls include the needed
hidden fallback inputs automatically.

`disabled()` disables the control and its fallbacks. Use CakePHP validation for
rules such as “choose at least two topics”; marking a checkbox group required
does not make every checkbox mandatory.
