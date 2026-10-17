<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Field\BoundField> $inputs
 * @var array{caption?: string} $templateData
 */
?>
<section id="<?= Html::text((string)$attributes->values['id']) ?>" class="inline-range" data-caption="<?= Html::text($templateData['caption'] ?? '') ?>">
    <aside><?= $inputs['end']->label() ?><?= $inputs['end']->input() ?><?= $inputs['end']->errors() ?></aside>
    <main><?= $inputs['start']->label() ?><?= $inputs['start']->input() ?><?= $inputs['start']->errors() ?></main>
</section>
