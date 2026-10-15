<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Embed;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;

/**
 * Tests multi-level scope composition with the original request.
 */
#[Form('envelope')]
#[Embed('post', PostForm::class)]
class EnvelopeForm extends FormBlueprint
{
}
