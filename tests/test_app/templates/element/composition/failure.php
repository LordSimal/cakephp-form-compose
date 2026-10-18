<?php
declare(strict_types=1);
/** @var \FormCompose\Blueprint\BlueprintView $form */
echo (string) $form->control('title');
echo (string) $form->embedded(path: 'author');
throw new RuntimeException('Definition layout failed.');
