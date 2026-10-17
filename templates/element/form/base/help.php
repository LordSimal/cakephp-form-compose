<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<p<?= (string)$attributes ?>><?= $field->state->helpText() ?></p>
