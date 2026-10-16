<?php
declare(strict_types=1);
/**
 * @var \FormCompose\RepeatedView $embed
 * @var array<int|string, \FormCompose\DefinitionView> $rows
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= (string)$attributes->merge(['class' => 'comments']) ?>>
<?php foreach ($rows as $row): ?>
    <article class="comment-row"><?= $row->render() ?></article>
<?php endforeach; ?>
<?= $embed->errors() ?>
</section>
