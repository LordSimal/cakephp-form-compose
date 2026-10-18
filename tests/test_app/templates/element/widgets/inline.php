<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Form\Field\BoundField> $inputs
 * @var array{caption?: string} $templateData
 */
?>
<section id="<?= Html::text((string)$attributes->values['id']) ?>" class="inline-range" data-caption="<?= Html::text($templateData['caption'] ?? '') ?>">
    <aside><?= (string) $inputs['end']->label() ?><?= (string) $inputs['end']->input() ?><?= (string) $inputs['end']->errors() ?></aside>
    <main><?= (string) $inputs['start']->label() ?><?= (string) $inputs['start']->input() ?><?= (string) $inputs['start']->errors() ?></main>
</section>
