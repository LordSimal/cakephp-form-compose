<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Blueprint\BlueprintView $form
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, mixed> $templateData
 */
?>
<section<?= (string)$attributes->merge(['data-author' => true]) ?>>
    <h2><?= Html::text((string)$templateData['heading']) ?></h2>
    <div class="grid grid-cols-2 gap-4">
        <?= (string) $form->controls(['first_name', 'last_name']) ?>
    </div>
    <?php if ($form->has('email')): ?>
        <section data-email><?= (string) $form->control('email') ?></section>
    <?php endif; ?>
    <?= (string) $form->controls(['internal', 'id']) ?>
</section>
