<?php
declare(strict_types=1);
use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var string $value
 */
?>
<textarea<?= (string)$attributes ?>><?= Html::text($value) ?></textarea>
