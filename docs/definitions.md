---
description: Build reusable field collections, customize them per screen, and render them wherever they belong in a CakePHP form.
---

# Reusable field definitions

A `FormDefinition` is an ordered collection of field configuration. Use it when
several screens share fields or when you assemble fields outside a view. Your PHP
template still determines the form's structure and where the collection appears.

Definitions are optional. For class-level attributes and dynamic configuration,
see [form blueprints](./blueprints.md). They use the same field resolution and PHP control
templates as ordinary [`control()` calls](./usage.md#complete-controls).

## Build once, render where needed

```php
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;

$contact = FormDefinition::make()
    ->add(FieldDefinition::make('name')->label('Full name'))
    ->add(FieldDefinition::make('email', 'email')->help('Use your work address.'));

$form = $this->Form->bind($user);

echo $form->open();
echo $form->control('company');
echo $form->definition($contact);
echo $form->control('newsletter', ['type' => 'checkbox']);
echo $form->submit('Save');
echo $form->close();
```

`add()` appends in call order. `$form->definition($contact)` immediately returns
the rendered controls as HTML. It adds no wrapper, opening tag, closing tag, or
button; the existing form session handles those separately. You can render several
definitions with ordinary controls or application markup between them.

A definition stores configuration only. Values, validation errors, constraints,
input IDs, and names resolve from the current form when each field is used. The
same definition can therefore serve different records and separate form sessions.
An explicit field `value` still overrides bound data as usual.

## When definitions help

- **Create and edit screens:** share the common fields while adding screen-specific
  controls in each template.
- **Recurring sections:** use the same contact details in registration, profile,
  and administration forms.
- **Conditional fields:** assemble configuration according to permissions or
  application settings before passing it to the view.
- **Plugin defaults:** expose reusable field configuration that a host application
  can customize.
- **Different layouts:** reuse configuration while placing individual field parts
  differently on each screen.

For fields used in only one template, direct `control()` calls need less setup.
Definitions configure fields; validation, authorization, and persistence remain
application responsibilities.

## Customize without changing the shared definition

Every builder operation returns a new definition. Keep the return value when
extending a collection or applying overrides:

```php
$administrationContact = $contact->with('email', [
    'label' => 'Account email',
    'help' => 'Changing this address affects sign-in.',
]);
```

The original `$contact` keeps its label and help text. On their respective screens:

::: code-group

```php [Registration]
// Inside the registration form.
echo $form->definition($contact);
```

```php [Administration]
// Inside the administration form.
echo $form->definition($administrationContact);
```

:::

`with($path, $options)` replaces matching top-level option keys and keeps the
field's position. Other keys remain unchanged. Nested arrays such as `attributes`
are replaced as a whole; this follows `FieldDefinition::with()`.

Choose a different control element for one use in the same way:

```php
echo $form->definition(
    $contact->with('email', ['template' => 'forms/special-email']),
);
```

This selects `templates/element/forms/special-email.php`. See
[choosing a template](./usage.md#choose-a-template-for-one-control) for the element
and the `templates` option for individual parts.

### Share template data

Use the same option when a reusable field needs custom presentation metadata:

```php
$contact = $contact->with('email', [
    'template' => 'forms/special-email',
    'templateData' => ['prefix' => '@', 'account' => $account],
]);

echo $form->definition($contact);
```

The element and that field's parts receive a `$templateData` array. A later
`with()` or per-call override replaces the entire bag, like other nested field
options; pass `[]` to clear it. The original shared definition remains unchanged.
The [template data reference](./template-reference.md#template-data) explains
typed values, scope, and escaping.

::: tip Configuration stays local
Rendering `$form->definition($contact)` does not install session-wide defaults.
A later `$form->field('email')` or `$form->control('email')` uses its own options,
any definition explicitly selected at `bind()`, and context inference. Pass
`$contact->field('email')` explicitly to reuse that configuration.
:::

## Assemble fields conditionally

Build a definition in an application service or controller and pass it to the
view like any other value:

```php
$account = $contact;

if ($canManageAccounts) {
    $account = $account->add(
        FieldDefinition::make('active', 'checkbox')->label('Account enabled'),
    );
}
```

`$contact` is unchanged. `$account` contains the additional field only when the
condition is met. Omitting a control is a presentation decision; authorize changes
to submitted fields in the application too.

## Reuse configuration in a custom layout

`$contact->field('email')` returns a `FieldDefinition`: configuration without bound
data or rendered markup. Pass it to the existing form API to get a `BoundField`:

```php
$email = $form->field($contact->field('email'), ['label' => 'Contact address']);
```

The optional second argument provides per-call overrides. You can now arrange
the parts using the normal [field composition API](./usage.md#composing-field-parts).
For example, this replaces rendering the whole collection inside an open form:

```php
echo $form->control($contact->field('name'));
echo $form->control('phone');

echo '<section class="contact-email">';
echo $email->label();
echo $email->input(['class' => 'contact-input']);
echo $email->help();
echo $email->errors();
echo '</section>';
```

The extra phone field appears between the shared name and email fields. Layout
stays in PHP; no collection insertion methods are needed. Recurring markup belongs
in a [theme element](./themes.md).

Creating or looking up configuration, or resolving a bound field, registers no
submitted input. As usual, `input()` registers after rendering succeeds, and the
session includes rendered inputs in submission protection when closing.

## API and constraints

| Call | Result |
| --- | --- |
| `FormDefinition::make()` | An empty, immutable collection. |
| `new FormDefinition([$name, $email])` | A collection from an ordered list of `FieldDefinition` objects. |
| `$definition->add($field)` | A new collection with the field appended. |
| `$definition->with('email', $options)` | A new collection with that field's options overridden. |
| `$definition->has('email')` | Whether the collection contains this path. |
| `$definition->remove('email')` | A new collection without that field. |
| `$definition->field('email')` | The existing `FieldDefinition` for that exact path. |
| `$definition->fields` | The ordered list of field definitions, available for iteration. |
| `$form->definition()` | HTML for the collection selected at `bind()`, or an empty string. |
| `$form->definition($definition)` | HTML for the collection, rendered in the current open form. |

Field paths must be unique within a collection. Duplicate paths, unknown lookup
paths, and unknown override targets throw `InvalidArgumentException`. Dot-separated
paths such as `comments.0.body` work as they do in ordinary controls.

The form must be open when calling `definition()`, including for an empty
collection. An empty collection returns an empty string. File controls still
require a multipart form, and themes and custom field types work as usual.

Input IDs must remain unique across the entire form, including across collections
and ordinary controls. Render each field presentation once. If intentionally
presenting the same path again, provide a distinct explicit ID for each
presentation through field options. Keep form IDs unique across the page.

## Select defaults for the whole binding

Pass an immutable definition to `bind()` when individual field calls should share
its configuration:

```php
$form = $this->Form->bind($user, definition: $contact);
echo $form->open();
echo $form->control('email'); // Uses $contact's email configuration.
echo $form->close();
```

Alternatively, `$form->definition()` renders the selected collection in order.
Calling `definition($other)` renders that collection at the call site without
replacing the selected defaults. Explicit field definitions and per-call options
override binding defaults, using top-level replacement.

`$definition->has('email')` checks membership. `$definition->remove('email')`
returns a new collection without that field and preserves the original. Removing
an unknown path throws `InvalidArgumentException`.

Blueprint classes compile to this same immutable representation. See
[blueprint configuration and precedence](./blueprints.md#configuration-order).
