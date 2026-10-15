<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<?php if ($field->state->grouped()): ?>
<fieldset<?= (string)$attributes ?>>
    <?= $field->label() ?>
    <?= $field->input() ?>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</fieldset>
<?php else: ?>
<div<?= (string)$attributes ?>>
    <?= $field->label() ?>
    <?= $field->input() ?>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
<?php endif;
