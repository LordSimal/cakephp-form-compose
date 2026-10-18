<?php
declare(strict_types=1);

use FormCompose\View\Helper\FormHelper;

/**
 * @var \Cake\View\View $this
 * @var string $blueprint
 * @var bool $local
 * @var bool $customType
 */
$helper = $this->helpers()->get('Form');
assert($helper instanceof FormHelper, 'The configured helper must extend FormCompose FormHelper.');
$builder = $helper->form();
if (!$local) {
    $builder->blueprint($blueprint);
}

$form = $builder->bind();
echo $form->open();
echo (string)($local ? $form->blueprint($blueprint) : $form->blueprint());
if ($customType) {
    echo (string)$form->control('custom')->type('dependency-type');
}

echo $form->close();
