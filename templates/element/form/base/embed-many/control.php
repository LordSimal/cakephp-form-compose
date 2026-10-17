<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Blueprint\RepeatedView $embed
 * @var array<int|string, \FormCompose\Blueprint\BlueprintView> $rows
 * @var \FormCompose\Rendering\Attributes $attributes
 */
if ((string)$attributes !== '') {
    throw new InvalidArgumentException('Repeated embed wrapper attributes require a custom control template.');
}

foreach ($rows as $row) {
    echo $row->render();
}
echo $embed->errors();
