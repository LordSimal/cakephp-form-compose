<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<?php if ($field->state->grouped()): ?>
<fieldset<?= (string)$attributes ?>>
    <?= (string) $field->label() ?>
    <?= (string) $field->input() ?>
    <?= (string) $field->help() ?>
    <?= (string) $field->errors() ?>
</fieldset>
<?php else: ?>
<div<?= (string)$attributes ?>>
    <?= (string) $field->label() ?>
    <?= (string) $field->input() ?>
    <?= (string) $field->help() ?>
    <?= (string) $field->errors() ?>
</div>
<?php endif;
