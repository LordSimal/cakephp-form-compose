<?php
declare(strict_types=1);
/** @var \FormCompose\Blueprint\BlueprintView $form */
?>
<div class="comment-content">
    <?= (string) $form->control('id') ?>
    <?= (string) $form->control('body') ?>
</div>
