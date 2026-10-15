<?php
declare(strict_types=1);
use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Field\Choice $choice
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<div<?= (string)$choice->presentation['container'] ?>>
    <input<?= (string)$attributes ?>>
    <label<?= (string)$choice->presentation['label'] ?>><?= Html::text($choice->label) ?></label>
</div>
