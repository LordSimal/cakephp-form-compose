<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Field\BoundField> $inputs
 */
?>
<div<?= (string)$attributes ?>>
    <?php foreach ($inputs as $input): ?>
        <?= $input->control() ?>
    <?php endforeach; ?>
</div>
