<?php
declare(strict_types=1);
use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var \FormCompose\Rendering\Attributes $titleAttributes
 * @var string|\FormCompose\Rendering\Html $title
 * @var array<string, mixed> $templateData
 * @var list<\FormCompose\Form\FeedbackEntry> $entries
 */
?>
<aside<?= (string)$attributes ?>>
    <h2<?= (string)$titleAttributes ?>><?= Html::text($title) ?></h2>
    <p><?= Html::text((string)$templateData['intro']) ?></p>
    <?php foreach ($entries as $entry): ?>
        <?php foreach ($entry->messages as $message): ?>
            <p><?= Html::text($message) ?></p>
        <?php endforeach; ?>
    <?php endforeach; ?>
</aside>
