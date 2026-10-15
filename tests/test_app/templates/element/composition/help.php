<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, mixed> $templateData
 */
?>
<p<?= (string)$attributes ?>><?= Html::text((string)$templateData['message']) ?></p>
