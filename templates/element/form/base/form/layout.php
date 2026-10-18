<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Form\FormSession $form
 * @var string $body
 * @var string $summary
 * @var string|\FormCompose\Rendering\Html|false $submit
 * @var \FormCompose\Configuration\ButtonConfig $submitConfig
 */
echo $summary;
echo $body;
if ($submit !== false) {
    echo (string) $form->submit($submit)->apply($submitConfig);
}
