<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<p<?= (string)$attributes ?>><?= $field->state->helpText() ?></p>
