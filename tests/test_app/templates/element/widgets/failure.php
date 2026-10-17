<?php
declare(strict_types=1);
/**
 * @var array<string, \FormCompose\Field\BoundField> $inputs
 */
?>
<?= $inputs['start']->control() ?>
<?php throw new RuntimeException('Composite layout failed');
