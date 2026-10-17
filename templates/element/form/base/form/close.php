<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Form\FormState $state
 * @var \FormCompose\Form\FormSession $form
 * @var list<\FormCompose\Rendering\Attributes> $hidden
 */
?>
<?= $form->hiddenFields($hidden, $state->presentation) ?>
</form>
