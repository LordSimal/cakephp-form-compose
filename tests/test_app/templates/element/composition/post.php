<?php
declare(strict_types=1);
/** @var \FormCompose\Blueprint\BlueprintView $form */
?>
<article data-post>
    <?= $form->control('title') ?>
    <?= $form->embedded('author') ?>
    <?= $form->control('body') ?>
</article>
