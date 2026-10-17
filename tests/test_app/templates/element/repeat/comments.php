<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var \FormCompose\Blueprint\RepeatedView $embed
 * @var array<int|string, \FormCompose\Blueprint\BlueprintView> $rows
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= (string)$attributes->merge(['class' => 'comments']) ?>>
<h2><?= Html::text($embed->label()) ?></h2>
<?php foreach ($embed->rows() as $row): ?>
    <article class="comment-row"><?= $row->render() ?></article>
<?php endforeach; ?>
<?= $embed->errors() ?>
</section>
