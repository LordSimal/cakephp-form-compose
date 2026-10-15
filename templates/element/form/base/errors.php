<?php
declare(strict_types=1);
use FormCompose\Rendering\Html;

/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<ul<?= (string)$attributes ?>>
<?php foreach ($field->state->messages as $message): ?>
    <li><?= Html::text($message) ?></li>
<?php endforeach; ?>
</ul>
