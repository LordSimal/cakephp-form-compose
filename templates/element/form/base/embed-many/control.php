<?php
declare(strict_types=1);

/**
 * @var \FormCompose\RepeatedView $embed
 * @var array<int|string, \FormCompose\DefinitionView> $rows
 * @var \FormCompose\Rendering\Attributes $attributes
 */
$wrapped = (string)$attributes !== '';
if ($wrapped) { ?>
<div<?= (string)$attributes ?>>
<?php }
foreach ($rows as $row) {
    echo $row->render();
}
echo $embed->errors();
if ($wrapped) { ?>
</div>
<?php }
