<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array{marker?: string, content?: string|\FormCompose\Rendering\Html} $templateData
 * @var \Closure(): string $parent
 */
?>
<section<?= (string)$attributes->merge(['data-template-marker' => $templateData['marker'] ?? 'empty']) ?>>
    <span><?= Html::text($templateData['content'] ?? '') ?></span>
    <?= $parent() ?>
</section>
