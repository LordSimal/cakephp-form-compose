<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Embed;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;

/**
 * Redeclares a child and a leaf while retaining inherited declaration positions.
 */
#[Form('inherited-post', templateData: ['inherited' => true])]
#[Embed('author', AuthorForm::class)]
#[Field('title', label: 'Inherited title')]
#[Field('extra')]
class InheritedPostForm extends PostForm
{
}
