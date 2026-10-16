<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var string|\FormCompose\Rendering\Html $label
 * @var array{marker: string} $templateData
 */
?>
<a<?= (string)$attributes->merge(['data-marker' => $templateData['marker']]) ?>><?= Html::text($label) ?></a>
