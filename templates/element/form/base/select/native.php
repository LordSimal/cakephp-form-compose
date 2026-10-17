<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var list<\FormCompose\Form\Field\Choice> $choices
 * @var \Closure(\FormCompose\Form\Field\Choice): string $renderChoice
 */
?>
<select<?= (string)$attributes ?>>
<?php foreach ($choices as $choice): ?>
<?= $renderChoice($choice) ?>
<?php endforeach; ?>
</select>
