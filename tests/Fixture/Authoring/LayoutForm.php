<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Field;
use FormCompose\Blueprint\FormBlueprint;

/** Exercises complete-form layouts with locked data, feedback, and uploads. */
#[Field('id', type: 'hidden')]
#[Field('title')]
#[Field('attachment', type: 'file')]
class LayoutForm extends FormBlueprint
{
}
