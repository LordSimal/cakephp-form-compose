<?php
declare(strict_types=1);
use FormCompose\Rendering\Html;
/**
 * @var string|\FormCompose\Rendering\Html $label
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<button<?= (string)$attributes ?>><?= Html::text($label) ?></button>
