<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Attribute\Field;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;

/**
 * Extends inherited declarations without losing unrelated parent options or order.
 */
#[Field('email', label: 'Child email')]
#[Field('phone', type: 'tel')]
class ChildForm extends ProfileForm
{
}
