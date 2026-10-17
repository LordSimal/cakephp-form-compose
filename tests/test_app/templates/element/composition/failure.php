<?php
declare(strict_types=1);
/** @var \FormCompose\BlueprintView $form */
echo $form->control('title');
echo $form->embedded('author');
throw new RuntimeException('Definition layout failed.');
