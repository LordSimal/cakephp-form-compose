<?php
declare(strict_types=1);

use FormCompose\View\Helper\FormHelper;
use FormCompose\Configuration\FormConfig;

/**
 * @var \Cake\View\View $this
 * @var string $blueprint
 * @var bool $local
 * @var bool $customType
 */
$helper = $this->helpers()->get('Form');
assert($helper instanceof FormHelper, 'The configured helper must extend FormCompose FormHelper.');
$config = $local ? new FormConfig() : FormConfig::builder()->blueprint($blueprint);
$form = $helper->bind(config: $config);
echo $form->open();
echo (string)($local ? $form->blueprint($blueprint) : $form->blueprint());
if ($customType) {
    echo (string)$form->control('custom')->type('dependency-type');
}

echo $form->close();
