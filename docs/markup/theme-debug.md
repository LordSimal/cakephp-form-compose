---
title: Debug template selection
description: Inspect themeDebug comments to find the selected template, follow lookup attempts, and diagnose variants and overrides.
---

# Debug template selection

When a template change has no visible effect, inspect which file actually won.
The `themeDebug` setting adds lookup information as HTML comments around rendered
parts. It works with [themes](/themes), [variants](/variants), and
[explicit templates](/templates).

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

Explicit templates show `source: explicit override`. A `$parent()` call adds a
nested trace showing where lookup resumed. Each rendered part has its own trace,
so look for the part you intended to change, such as `control`, `label`, or `native`.

Internal CSRF and method inputs use compact comments identifying their purpose.
The traces omit values and security tokens. Enable them in development rather
than production output.

For full candidate order and scope rules, see the
[template reference](/template-reference#lookup-order).
