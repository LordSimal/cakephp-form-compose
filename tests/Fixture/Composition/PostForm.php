<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Embed;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\BlueprintBuilder;
use FormCompose\FormBlueprint;
use FormCompose\FormContext;

/**
 * Adjusts an already configured child without repeating its construction or hook.
 */
#[Form('composed-post', template: 'composition/post')]
#[Field('title')]
#[Embed('author', 'composed-author')]
#[Field('body', type: 'textarea')]
class PostForm extends FormBlueprint
{
    /**
     * Share the same injected service as nested authors.
     */
    public function __construct(protected readonly CompositionProbe $probe)
    {
    }

    /**
     * Override nested fields after the child has applied its own configuration.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $this->probe->record('post', $context);
        $form->with('author.first_name', ['label' => 'Author name from parent']);
        if ($context->request->getAttribute('stripParentEmail') === true) {
            $form->remove('author.email');
        }
    }
}
