---
title: Choice controls
description: Typed choices, groups, enums, native selection, checkboxes, and empty-selection fallbacks.
---

# Choice controls

Inside an open form, selection comes from the bound value. Use `value()` only
for deliberate overrides. See [choice settings](/field-options#choices-and-fallbacks).

## A select list

```php
echo $form->control('status')
    ->choices(['draft' => 'Draft', 'published' => 'Published'])
    ->placeholder('Choose a status');
```

`choices()` infers a select unless an explicit type overrides it. Optional single
selects receive a blank placeholder by default. `placeholder(false)` suppresses
it; `placeholder(true)` requests a blank option even for a required select.

::: info Empty placeholder and empty-string choice
A real `''` choice remains a choice and takes precedence over generated
placeholders. Both submit an empty string; use another value when your application
needs to distinguish them. Zero is also a real choice value.
:::

## Backed enums

```php
use AppStatus;
use Enum\Status;

echo $form->control('status')->choices(Status::class);
```

A backed enum class renders all cases in declaration order. Cases submit their
backing values; labels default to humanized case names. CakePHP schema `enum-*`
types discover their backed enum automatically, including nested associations.
Explicit `choices([])` or `choices(null)` opts out of discovery.

### Custom enum labels

```php
use CakeEnumLabelInterface;
use Database\Type\EnumLabelInterface;
use I18n\__;
use function Cake__;

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

CakePHP's `EnumLabelInterface` supplies translated labels. They are escaped like
ordinary text. Pure enums fail because they have no submitted backing value.

### Subsets and multiple selections

```php
echo $form->control('status')->type('radio')->choices([Status::Published, Status::Draft]);
echo $form->control('allowed_statuses')
    ->type('multicheckbox')
    ->choices(Status::class)
    ->value([Status::Draft, Status::Published]);
```

Iterable keys do not replace enum backing values. Bound scalar values and enum
cases select the corresponding choice. Explicit native non-choice types keep
their type and do not consume choice iterators.

## Groups and attributes on individual choices

Simple maps remain convenient data. Use typed configs for choice metadata:

```php
use Configuration\ChoiceConfig;
use Configuration\ChoiceGroupConfig;
use FormComposeChoiceConfig;
use FormComposeChoiceGroupConfig;

echo $form->control('audience')->choices([
    new ChoiceGroupConfig('Public', [
        'everyone' => 'Everyone',
        'subscribers' => 'Subscribers',
    ]),
    ChoiceGroupConfig::builder('Internal')
        ->choices([
            ChoiceConfig::builder('staff', 'Staff')->attributes(['data-tier' => 'internal']),
            new ChoiceConfig(value: 'legacy', label: 'Legacy team', attributes: ['disabled' => true]),
        ])
        ->attributes(['data-group' => 'internal']),
]);
```

Nested maps such as `['Public' => ['everyone' => 'Everyone']]` also create groups.
Legacy extended entry arrays are replaced by `ChoiceConfig(value, label, attributes, id)`.
Choice builders expose `label()`, `id()`, `attributes()`, and `config()`; group
builders expose `choices()`, `attributes()`, and optional `config()`.
Pass inline builders directly; choice arrays capture them immediately. Lazy
iterables remain unconsumed and capture yielded builders during rendering. Omitted enum labels
use the enum's label; explicit null or empty string creates a blank label.
Labels are escaped unless wrapped in trusted `Html`.

## Radio buttons

```php
echo $form->control('visibility')
    ->type('radio')
    ->label('Who can read this article?')
    ->choices(['public' => 'Everyone', 'private' => 'Editors only']);
```

Groups use fieldset/legend semantics and unique labeled choices. Required radio
sets use native browser behavior. Required multi-checkbox groups do not require
every box; enforce minimum selections in CakePHP validation.

## A single checkbox

```php
echo $form->control('published')->type('checkbox')->label('Publish this article');
```

The default checked value is `'1'`; a hidden fallback submits `'0'` when unchecked.
Boolean true and integer 1 bind as checked. Customize both values:

```php
echo $form->control('approval')
    ->type('checkbox')
    ->checkedValue('approved')
    ->uncheckedValue('pending');
```

`value()` controls the bound selection; `checkedValue()` controls submission.

## Multiple selections

```php
echo $form->control('topics')->multiple()->choices(['php' => 'PHP', 'testing' => 'Testing']);
```

Or use `type('multicheckbox')` instead of a multiple select. Both submit under
`topics[]`. An array of scalar values or backed enum cases selects entries.
For ORM associations use `tags._ids`; see [associations](/associations).

## Empty selections and disabled controls

Browsers omit unchecked/empty selections. FormCompose supplies hidden fallbacks
for checkboxes, radio groups, multi-checkboxes, and multiple selects. Other than
a single checkbox's configured fallback, these submit `''` under the base name
without `[]`. `fallback(false)` permits omission instead.

`disabled()` disables the input, choices, and fallback, registering no native
submitted names. Individual `ChoiceConfig` attributes can disable one choice.
HTML readonly has no effect on selects, checkboxes, or radio buttons; enforce
allowed changes through application authorization and validation.
