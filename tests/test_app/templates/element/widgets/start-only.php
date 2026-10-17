<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Form\Field\BoundField> $inputs
 */
?>
<section id="<?= Html::text((string)$attributes->values['id']) ?>"><?= $inputs['start']->control() ?></section>
