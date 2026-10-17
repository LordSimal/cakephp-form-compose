<?php
declare(strict_types=1);

/** @var \FormCompose\Blueprint\BlueprintView $form */
$email = $form->field('email', array_replace(['label' => 'Explicit email'], ['help' => '<Parts help>']));
?>
<aside data-parts>
    <?= $email->label() ?>
    <?= $email->input() ?>
    <?= $email->help() ?>
    <?= $email->errors() ?>
</aside>
