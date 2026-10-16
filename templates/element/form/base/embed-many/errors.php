<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var list<string> $messages
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<ul<?= (string)$attributes ?>>
<?php foreach ($messages as $message): ?>
    <li><?= Html::text($message) ?></li>
<?php endforeach; ?>
</ul>
