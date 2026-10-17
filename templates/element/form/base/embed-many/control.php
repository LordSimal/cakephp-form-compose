<?php
declare(strict_types=1);

/**
 * @var \FormCompose\RepeatedView $embed
 * @var array<int|string, \FormCompose\DefinitionView> $rows
 * @var \FormCompose\Rendering\Attributes $attributes
 */
if ((string)$attributes !== ''):
    throw new InvalidArgumentException('Repeated embed wrapper attributes require a custom control template.');
endif;

foreach ($rows as $row):
    echo $row->render();
endforeach;
echo $embed->errors();
