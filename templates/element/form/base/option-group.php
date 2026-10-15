<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Field\Choice $choice
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var \Closure(\FormCompose\Field\Choice): string $renderChoice
 */
?>
<optgroup<?= (string)$attributes->replace(['label' => $choice->label]) ?>>
<?php foreach ($choice->children as $child): ?>
<?= $renderChoice($child) ?>
<?php endforeach; ?>
</optgroup>
