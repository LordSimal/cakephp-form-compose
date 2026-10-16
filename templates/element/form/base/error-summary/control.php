<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var list<\FormCompose\Form\FeedbackEntry> $entries
 * @var string|\FormCompose\Rendering\Html $title
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var \FormCompose\Rendering\Attributes $titleAttributes
 */
?>
<section<?= (string)$attributes ?>>
    <h2<?= (string)$titleAttributes ?>><?= Html::text($title) ?></h2>
    <ul>
    <?php foreach ($entries as $entry): ?>
        <?php foreach ($entry->messages as $message): ?>
        <li>
            <?php if ($entry->target !== null): ?>
            <a href="<?= Html::text('#' . rawurlencode($entry->target)) ?>"><?= Html::text($entry->label) ?>: <?= Html::text($message) ?></a>
            <?php else: ?>
            <?= Html::text($entry->label) ?>: <?= Html::text($message) ?>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </ul>
</section>
