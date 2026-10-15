<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var list<\FormCompose\Field\Choice> $choices
 * @var \Closure(\FormCompose\Field\Choice): string $renderChoice
 */
?>
<select<?= (string)$attributes ?>>
<?php foreach ($choices as $choice): ?>
<?= $renderChoice($choice) ?>
<?php endforeach; ?>
</select>
