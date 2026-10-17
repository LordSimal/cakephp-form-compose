<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var \FormCompose\Form\FormSession $form
 * @var string $body
 * @var string $summary
 * @var string|\FormCompose\Rendering\Html|false $submit
 * @var array<string, mixed> $submitOptions
 * @var array{caption?: string} $templateData
 */
?>
<article data-form-layout>
    <h2><?= Html::text($templateData['caption'] ?? 'Editor') ?></h2>
    <?= $summary ?>
    <div data-form-body><?= $body ?></div>
    <footer>
        <?= $form->submit('Check', ['name' => 'intent', 'value' => 'check', 'formnovalidate' => true]) ?>
        <?php if ($submit !== false): ?>
            <?= $form->submit($submit, $submitOptions) ?>
        <?php endif; ?>
    </footer>
</article>
