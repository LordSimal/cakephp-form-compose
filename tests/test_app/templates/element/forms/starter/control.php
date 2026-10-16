<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
$tag = $field->state->grouped() ? 'fieldset' : 'div';
?>
<<?= $tag ?><?= (string)$attributes->merge(['class' => 'form-field']) ?>>
    <?php if ($field->state->type === 'checkbox') { ?>
        <div class="checkbox-row">
            <?= $field->input() ?>
            <?= $field->label() ?>
        </div>
    <?php } else { ?>
        <?= $field->label() ?>
        <?= $field->input() ?>
    <?php } ?>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</<?= $tag ?>>
