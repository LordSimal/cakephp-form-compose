<?php
declare(strict_types=1);
/** @var \FormCompose\BlueprintView $form */
?>
<article data-post>
    <?= $form->control('title') ?>
    <?= $form->embedded('author') ?>
    <?= $form->control('body') ?>
</article>
