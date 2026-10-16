<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var string|\FormCompose\Rendering\Html $label
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<a<?= (string)$attributes ?>><?= Html::text($label) ?></a>
