<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Form\FormSession $form
 * @var string $body
 * @var string $summary
 * @var string|\FormCompose\Rendering\Html|false $submit
 * @var array<string, mixed> $submitOptions
 */
echo $summary;
echo $body;
if ($submit !== false) {
    echo $form->submit($submit, $submitOptions);
}
