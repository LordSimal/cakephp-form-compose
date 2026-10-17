<?php
declare(strict_types=1);
/**
 * @var list<\FormCompose\Field\Choice> $choices
 * @var \Closure(\FormCompose\Field\Choice): string $renderChoice
 */
foreach ($choices as $choice):
    echo $renderChoice($choice);
endforeach;
