<?php
declare(strict_types=1);
use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Field\Choice $choice
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var \Closure(\FormCompose\Field\Choice): string $renderChoice
 */
?>
<fieldset<?= (string)$attributes ?>>
    <legend><?= Html::text($choice->label) ?></legend>
<?php foreach ($choice->children as $child): ?>
<?= $renderChoice($child) ?>
<?php endforeach; ?>
</fieldset>
