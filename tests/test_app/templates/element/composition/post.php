<?php
declare(strict_types=1);
/** @var \FormCompose\Blueprint\BlueprintView $form */
?>
<article data-post>
    <?= (string) $form->control('title') ?>
    <?= (string) $form->embedded(path: 'author') ?>
    <?= (string) $form->control('body') ?>
</article>
