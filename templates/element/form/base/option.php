<?php
declare(strict_types=1);
use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Form\Field\Choice $choice
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<option<?= (string)$attributes ?>><?= Html::text($choice->label) ?></option>
