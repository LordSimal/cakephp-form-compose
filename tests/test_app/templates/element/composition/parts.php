<?php
declare(strict_types=1);

use FormCompose\Configuration\FieldConfig;

/** @var \FormCompose\Blueprint\BlueprintView $form */
$email = $form->field('email')
    ->apply(
        FieldConfig::builder()
            ->label('Explicit email')
            ->config()
            ->merge(FieldConfig::builder()->help('<Parts help>')->config()),
    )
    ->resolve();
?>
<aside data-parts>
    <?= (string) $email->label() ?>
    <?= (string) $email->input() ?>
    <?= (string) $email->help() ?>
    <?= (string) $email->errors() ?>
</aside>
