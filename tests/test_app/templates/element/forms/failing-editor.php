<?php
declare(strict_types=1);

use FormCompose\FormSession;

/** @var FormSession $form */
echo $form->submit('Partial action');
throw new RuntimeException('Form layout failed.');
