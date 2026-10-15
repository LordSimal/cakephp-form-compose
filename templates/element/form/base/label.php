<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<?php if ($field->state->grouped()): ?>
<legend<?= (string)$attributes ?>><?= $field->state->labelText() ?></legend>
<?php else: ?>
<label<?= (string)$attributes ?>><?= $field->state->labelText() ?></label>
<?php endif;
