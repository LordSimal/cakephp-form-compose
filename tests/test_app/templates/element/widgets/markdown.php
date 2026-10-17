<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var string $value
 */
?>
<textarea<?= (string)$attributes->merge(['data-editor' => 'markdown']) ?>><?= Html::text($value) ?></textarea>
