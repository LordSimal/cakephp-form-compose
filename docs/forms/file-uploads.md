---
title: File uploads
description: Render file inputs in a multipart form and accept one or several uploaded files.
---

# File uploads

Enable multipart encoding before binding, then render a file input. Here the
controller supplies the `$article` being edited.

`templates/Articles/edit.php`

```php {4} [templates/Articles/edit.php]
<?php
$form = $this->Form
    ->form($article)
    ->multipart()
    ->bind();
?>
<?= $form->open() ?>
<?= $form->control('attachment')
    ->type('file')
    ->label('PDF attachment')
    ->attributes(['accept' => 'application/pdf']) ?>
<?= $form->submit('Upload') ?>
<?= $form->close() ?>
```

`multipart()` sets the encoding needed to send files. It keeps the form's HTTP
method, including the method override used when editing an existing entity.
Rendering a file input without multipart encoding throws an exception.

The browser's `accept` attribute helps users choose a file; validate uploads in
your application. Read the upload through CakePHP's
`$this->request->getUploadedFile('attachment')`, then apply your existing storage
and persistence workflow.

## Accept several files

Replace the attachment control with:

```php
echo $form->control('attachments')
    ->type('file')
    ->multiple()
    ->attributes(['accept' => 'application/pdf']);
```

This submits `attachments[]`. The uploaded files are available under
`$this->request->getUploadedFiles()['attachments']`.

File inputs never pre-fill from existing values. Show an existing attachment's
name or download link separately from the upload control.
