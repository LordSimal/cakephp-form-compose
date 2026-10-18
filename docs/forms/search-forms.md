---
title: Search forms
description: Build a GET search form and keep its filters filled from URL query parameters.
---

# Search forms

A search form should put filters in the URL and keep them filled when results
reload. For `/articles/index?q=cakephp`, the search input below displays
`cakephp`.

`templates/Articles/index.php`

```php {6} [templates/Articles/index.php]
<?php
$form = $this->Form
    ->form()
    ->method('get')
    ->url(['controller' => 'Articles', 'action' => 'index'])
    ->valueSources(['query'])
    ->bind();
?>
<?= $form->open() ?>
<?= $form->control('q')
    ->type('search')
    ->label('Search articles') ?>
<?= $form->submit('Search') ?>
<?= $form->close() ?>
```

`method('get')` controls how the browser submits the form.
`valueSources(['query'])` tells the form where to read values when displaying it:
the current URL's query parameters. Both settings are needed for this example.
Your controller reads `$this->request->getQuery('q')` to filter the results.

## Add a filter

Place another control before the submit button:

```php
echo $form->control('status')
    ->choices([
        'draft' => 'Draft',
        'published' => 'Published',
    ])
    ->placeholder('Any status');
```

Visiting `?q=cakephp&status=published` fills the search input and selects
“Published”. Clearing the select submits an empty value; your controller decides
which filters to apply.

## Provide a fallback

Use a field default when the URL has no value:

```php
echo $form->control('q')
    ->type('search')
    ->default('cakephp');
```

To fall back to a bound object, use `form($filters)` with
`valueSources(['query', 'context'])`. Here `context` means the object or CakePHP
context passed to `form()`. Sources are checked in the order supplied.
The [value-source reference](/form-reference#value-sources) describes all sources
and precedence rules.
