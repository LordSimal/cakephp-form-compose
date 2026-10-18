---
title: Debug template selection
description: Inspect themeDebug comments to find the selected template, follow lookup attempts, and diagnose variants and overrides.
---

# Debug template selection

When a template change has no visible effect, inspect which file actually won.
The `themeDebug` setting adds lookup information as HTML comments around rendered
parts. It works with [themes](./themes.md), [variants](./variants.md), and
[explicit templates](./templates.md).

## Enable debug comments

Enable `themeDebug` on the helper to see which PHP elements rendered and why they
were selected. For example, in `AppView::initialize()`:

```php
use Cake\Core\Configure;
use FormCompose\Rendering\Theme;

$this->addHelper('Form', [
    'className' => 'FormCompose.Form',
    'theme' => new Theme(['forms/application']),
    'themeDebug' => Configure::read('debug'),
]);
```

## Read a lookup trace

Render controls normally. Inspect page source or comment nodes in the browser's
developer tools. For an email control, its label portion might look like this:

```html
<!-- FormCompose BEGIN
scope: field
part: label
type: email
field: email
source: layer FormCompose.form/base
element: FormCompose.form/base/label
lookup:
  MISS forms/application/email/label
  MISS forms/application/label
  MISS FormCompose.form/base/email/label
  MATCH FormCompose.form/base/label
-->
<label for="form-compose-1-email">Email</label>
<!-- FormCompose END part=label -->
```

::: info Read the lookup list
`MISS` entries show candidates that did not match; `MATCH` identifies the selected
element. The list includes only candidates actually attempted and stops at the
winner. A requested `variant` is tried before type-specific and generic candidates
within each layer.
:::

Element names are CakePHP identifiers, not absolute filesystem paths.
`forms/application/email/control` normally maps to
`templates/element/forms/application/email/control.php`. Plugin identifiers such
as `FormCompose.form/base/label` use CakePHP's plugin element resolution, including
application overrides of plugin elements.

## Follow overrides and parent calls

Explicit `template` or `templates` overrides show `source: explicit override`.
When an element calls `$parent()`, a `FormCompose PARENT` comment precedes a nested
trace showing where lookup resumed. Nested parts also get their own boundaries:
an input can pass through `input.php`, then `select/native.php`, then individual
option elements. This helps identify the part to override.

## Recognize infrastructure inputs

CakePHP's internal hidden inputs use a compact comment showing the field name
and purpose. For example, a CSRF input is preceded by:

```html
<!-- FormCompose internal hidden field: _csrfToken (CSRF protection) -->
```

The same format identifies `_method`, `_Token[fields]`, `_Token[unlocked]`, and
`_Token[debug]`. These internal inputs omit the full lookup trace. Other form
hidden inputs retain their full trace with a field name and purpose.

::: info Debug traces omit submitted values
Traces show template identifiers, field names, and infrastructure purposes. They
omit values, other attributes, labels, validation messages, `templateData`, and
security tokens. Token values and hashes are never repeated in comments.
:::

## Configuration and rendering details

`themeDebug` defaults to `false` and enables comments only when set to `true`.
The example ties it to CakePHP's debug setting; you can enable it explicitly with
`$this->Form->setConfig('themeDebug', true)`. Configuration affects future bindings
and standalone action calls. Existing sessions retain their setting.

Empty parts remain empty, and template exceptions still propagate normally.
Inspection methods that do not render templates produce no comments. Handwritten
markup and ordinary CakePHP elements called directly by application templates do
not receive their own FormCompose trace.

The helper setting configures its default `PhpRenderer`. A renderer supplied with
`renderer:` keeps its own behavior. To enable tracing on an explicitly constructed
PHP renderer, use `new PhpRenderer($view, $theme, themeDebug: true)` and import
`FormCompose\Rendering\PhpRenderer`.

::: details Comment formatting

Comment boundaries and rendered parts are separated by newlines for readable
page source. Debug mode therefore adds formatting whitespace between elements;
control values remain unchanged. Comments use two spaces per render nesting level,
including `$parent()` delegation. This reflects the rendering hierarchy rather
than the surrounding page's HTML indentation. The first HTML tag of each part
aligns with its comments. Lines inside controls and existing nested traces stay
unchanged, preserving multiline textarea values and other content.

:::
