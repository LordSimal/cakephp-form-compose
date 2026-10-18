<?php
declare(strict_types=1);

use FormCompose\Form\FormSession;

/** @var FormSession $form */
echo (string) $form->submit('Partial action');
throw new RuntimeException('Form layout failed.');
