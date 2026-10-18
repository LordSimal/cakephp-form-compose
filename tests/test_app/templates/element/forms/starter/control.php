<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
$tag = $field->state->grouped() ? 'fieldset' : 'div';
?>
<<?= $tag ?><?= (string)$attributes->merge(['class' => 'form-field']) ?>>
    <?php if ($field->state->type === 'checkbox'): ?>
        <div class="checkbox-row">
            <?= (string) $field->input() ?>
            <?= (string) $field->label() ?>
        </div>
    <?php else: ?>
        <?= (string) $field->label() ?>
        <?= (string) $field->input() ?>
    <?php endif; ?>
    <?= (string) $field->help() ?>
    <?= (string) $field->errors() ?>
</<?= $tag ?>>
