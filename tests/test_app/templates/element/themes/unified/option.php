<?php
declare(strict_types=1);
use FormCompose\Rendering\Html;
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var \FormCompose\Form\Field\Choice $choice
 */
?>
<option data-option<?= (string)$attributes ?>><?= Html::text($choice->label) ?></option>
