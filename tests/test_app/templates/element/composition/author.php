<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\DefinitionView $form
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, mixed> $templateData
 */
?>
<section<?= (string)$attributes->merge(['data-author' => true]) ?>>
    <h2><?= Html::text((string)$templateData['heading']) ?></h2>
    <div class="grid grid-cols-2 gap-4">
        <?= $form->controls(['first_name', 'last_name']) ?>
    </div>
    <?php if ($form->has('email')): ?>
        <section data-email><?= $form->control('email') ?></section>
    <?php endif; ?>
    <?= $form->controls(['internal', 'id']) ?>
</section>
