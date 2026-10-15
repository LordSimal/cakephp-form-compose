<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var array{marker?: string} $templateData
 * @var \Closure(): string $parent
 */
?>
<!-- template-data: <?= Html::text($templateData['marker'] ?? 'empty') ?> -->
<?= $parent() ?>
