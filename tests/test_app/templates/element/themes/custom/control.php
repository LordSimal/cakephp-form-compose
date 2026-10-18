<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= (string)$attributes->merge(['data-control' => 'custom']) ?>>
    <?= (string) $field->label() ?>
    <div class="email-input">
        <span aria-hidden="true">@</span>
        <?= (string) $field->input() ?>
    </div>
    <?= (string) $field->help() ?>
    <?= (string) $field->errors() ?>
</section>
