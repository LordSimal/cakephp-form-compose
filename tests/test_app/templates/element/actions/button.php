<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var string|\FormCompose\Rendering\Html $label
 * @var array{marker: string} $templateData
 */
?>
<button<?= (string)$attributes->merge(['data-marker' => $templateData['marker']]) ?>><?= Html::text($label) ?></button>
