<?php
declare(strict_types=1);

use FormCompose\Field\FieldDefinition;

/** @var \FormCompose\DefinitionView $form */
$email = $form->field(FieldDefinition::make('email')->label('Explicit email'), ['help' => '<Parts help>']);
?>
<aside data-parts>
    <?= $email->label() ?>
    <?= $email->input() ?>
    <?= $email->help() ?>
    <?= $email->errors() ?>
</aside>
