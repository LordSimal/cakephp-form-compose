<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var string $value
 */
?>
<input<?= (string)$attributes->replace(['type' => 'text', 'value' => $value]) ?>>
