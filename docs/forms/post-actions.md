---
title: POST buttons and links
description: Add delete buttons, optional confirmation, and independent actions inside another form.
---

# POST buttons and links

Use a POST action for a button such as “Delete” beside an article. It renders its
own protected form; you do not need a bound `$form` session.

## Delete an article

In an article view, with `$article` supplied by your controller:

```php
<?= $this->Form
    ->postButton(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete') ?>
```

The button works without JavaScript. `method('delete')` submits a POST with
CakePHP's DELETE method override. The default action method is POST.
Keep your controller's authorization and allowed-method checks.

## Add confirmation

Add `confirm()` to the button:

```php
<?= $this->Form
    ->postButton(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete')
    ->confirm('Delete this article?') ?>
```

Confirmation uses the plugin's enhancement script. The helper registers it in
CakePHP's `script` block automatically. Make sure your application layout renders
that block, usually in `<head>`:

```php:no-line-numbers
<?= $this->fetch('script') ?>
```

::: warning Keep authorization on the server
Without JavaScript, a native button submits without a confirmation dialog.
Confirmation helps the user avoid a mistake; your controller still authorizes
the action.
:::

## Render a link instead

```php
<?= $this->Form
    ->postLink(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete')
    ->confirm('Delete this article?') ?>
```

A post link needs JavaScript. It submits its associated hidden form rather than
visiting the destructive URL as a GET request. Use `postButton()` when the action
must work without JavaScript.

## Place actions inside another form

HTML forms cannot be nested. Use `block()` to display the delete trigger inside
the edit form while placing its independent form after it:

`templates/Articles/edit.php`

```php [templates/Articles/edit.php]
<?php
$form = $this->Form->bind($article);
?>
<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $this->Form
    ->postButton(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete')
    ->block('postActions') ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>

<?= $this->fetch('postActions') ?>
```

Render the `postActions` block outside every form. The delete button submits only
its own form; the title field belongs to the Save action.

## Pass additional data

```php
<?= $this->Form
    ->postButton(label: 'Archive', url: ['action' => 'archive', $article->id])
    ->data(['version' => $article->version]) ?>
```

The version renders as a locked hidden input when CakePHP form protection is
enabled. The controller receives it with the action request.

## Options and presentation

Use `attributes()` to style the trigger, and `template()` to select its element.
The [POST action reference](/post-action-reference#options-and-presentation)
lists all setters, payload restrictions, and template variables.

<span id="load-the-enhancement"></span>
<span id="custom-confirmation-dialogs"></span>

For manual script loading, asynchronous confirmation dialogs, or dynamically
inserted actions, see [JavaScript integration](/post-action-reference#load-the-enhancement).
