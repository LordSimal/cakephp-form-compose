<?php
declare(strict_types=1);
/** @var \FormCompose\DefinitionView $form */
?>
<article data-post>
    <?= $form->control('title') ?>
    <?= $form->embedded('author') ?>
    <?= $form->control('body') ?>
</article>
