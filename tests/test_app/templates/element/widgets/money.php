<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Field\BoundField> $inputs
 */
?>
<div<?= (string)$attributes->replace([
    'name' => null, 'required' => null, 'disabled' => null, 'readonly' => null,
])->merge(['class' => 'money-fields']) ?>>
    <div class="money-amount"><?= $inputs['amount']->control() ?></div>
    <div class="money-currency"><?= $inputs['currency']->control() ?></div>
</div>
