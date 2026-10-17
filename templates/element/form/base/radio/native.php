<?php
declare(strict_types=1);
/**
 * @var list<\FormCompose\Form\Field\Choice> $choices
 * @var \Closure(\FormCompose\Form\Field\Choice): string $renderChoice
 */
foreach ($choices as $choice) {
    echo $renderChoice($choice);
}
