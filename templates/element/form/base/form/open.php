<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Form\FormState $state
 * @var \FormCompose\Form\FormSession $form
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var list<\FormCompose\Rendering\Attributes> $hidden
 */
?>
<form<?= (string)$attributes ?>>
<?= $form->hiddenFields($hidden, $state->presentation) ?>
