---
title: Choice controls
description: Build select lists, radio groups, single checkboxes, and multiple selections with explicit values and fallback behavior.
---

# Choice controls

These examples replace controls inside an open form. Selection comes from the
bound value; use field `value` only when intentionally overriding it. See
[Field options](./field-options.md#choices-and-fallbacks) for defaults.

## A select list

```php
echo $form->control('status', [
    'type' => 'select',
    'options' => [
        'draft' => 'Draft',
        'published' => 'Published',
    ],
    'empty' => 'Choose a status',
]);
```

An entity with `status === 'draft'` selects Draft. Including `options` also infers
`select` if no type is specified. `empty` supplies a placeholder label. Optional
single selects have a blank option by default, so a nullable foreign key does not
silently display the first record. Use `empty => false` to suppress it or
`empty => true` to request a blank option on a required select. An explicit empty
key in `options` also works and takes precedence over `empty`.

## Groups and attributes on individual choices

Nested maps produce option groups. Extended entries use `value` and `text`;
additional keys are HTML attributes on that choice:

```php
echo $form->control('audience', [
    'type' => 'select',
    'options' => [
        '' => 'Choose an audience',
        'Public' => [
            'everyone' => 'Everyone',
            'subscribers' => 'Subscribers',
        ],
        'Internal' => [
            ['value' => 'staff', 'text' => 'Staff', 'data-tier' => 'internal'],
            ['value' => 'legacy', 'text' => 'Legacy team', 'disabled' => true],
        ],
    ],
]);
```

Use `value` in an extended entry so it is recognized as a choice rather than a
group. For radio/checkbox choices, an optional `id` overrides the generated choice
ID. Choice text is escaped unless explicitly wrapped in trusted `Html`.

## Radio buttons

The same choice map can render as a group of radio buttons:

```php
echo $form->control('visibility', [
    'type' => 'radio',
    'label' => 'Who can read this article?',
    'options' => ['public' => 'Everyone', 'private' => 'Editors only'],
]);
```

The bundled control uses a fieldset and legend, with a separate label and unique
ID for each choice. A bound value of `'private'` checks Editors only.

## A single checkbox

```php
echo $form->control('published', [
    'type' => 'checkbox',
    'label' => 'Publish this article',
]);
```

The default checked value is `'1'`; a hidden fallback submits `'0'` when unchecked.
A boolean true or integer 1 binds as checked. Customize both values for another
representation:

```php
echo $form->control('approval', [
    'type' => 'checkbox',
    'checkedValue' => 'approved',
    'uncheckedValue' => 'pending',
    'label' => 'Approve this article',
]);
```

Here a bound value of `'approved'` checks the box. `value` is the bound selection;
`checkedValue` is what the browser submits when checked.

## Multiple selections

Use an array-valued field with a multiple select or a checkbox group:

::: code-group

```php [Multiple select]
echo $form->control('topics', [
    'type' => 'select',
    'multiple' => true,
    'options' => ['php' => 'PHP', 'cakephp' => 'CakePHP', 'testing' => 'Testing'],
]);
```

```php [Checkbox group]
echo $form->control('topics', [
    'type' => 'multicheckbox',
    'options' => ['php' => 'PHP', 'cakephp' => 'CakePHP', 'testing' => 'Testing'],
]);
```

:::

These are alternative presentations of the same field. Both submit selected
values under `topics[]`; `['php', 'testing']` selects PHP and Testing.
For ORM associations, use paths such as `tags._ids` and read the
[association guide](./associations.md).

A required checkbox group does not add HTML `required` to each choice: that
would force the user to select every box. Enforce “choose at least one” with
CakePHP validation. Required radio groups use the browser's native group behavior.

## Empty selections and disabled controls

Browsers omit unchecked checkboxes and empty multiple selections. FormCompose
adds a hidden fallback before checkboxes, radio groups, multi-checkbox groups,
and multiple selects. Except for single checkboxes, the fallback value is an
empty string under the base name (without `[]`). CakePHP handles association
marshalling in the normal application submission flow.

`hiddenField => false` omits the fallback, allowing the browser to omit an empty
selection entirely. Choose that only when omission matches your application logic.

```php
echo $form->control('archived', [
    'type' => 'checkbox',
    'disabled' => true,
]);
```

A disabled field disables its fallback and all its choices, and registers no
native submitted names. A choice can be disabled individually using an extended
entry. `readonly` has no native effect on selects, checkboxes, or radio buttons;
use appropriate browser semantics and authorize changes in the application.
