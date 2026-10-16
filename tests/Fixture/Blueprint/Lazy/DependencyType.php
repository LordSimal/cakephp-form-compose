<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Lazy;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Field\BoundField;
use FormCompose\Field\FieldTypeInterface;
use FormCompose\Field\InputIdentityProviderInterface;
use FormCompose\Field\Submission;
use FormCompose\Rendering\Attributes;

/**
 * Records real container construction and exposes an additional composite identity.
 */
#[AsFieldType('dependency-type')]
class DependencyType implements FieldTypeInterface, InputIdentityProviderInterface
{
    /**
     * Record each independently constructed type instance.
     */
    public function __construct(protected readonly TypeProbe $probe)
    {
        $this->probe->constructions++;
    }

    /**
     * Render one submitted input with an additional decorative DOM identity.
     */
    public function render(BoundField $field, Attributes $attributes): string
    {
        return (
            '<input'
                . (string)$attributes
                . '><span'
                . (string)new Attributes([
                    'id' => $field->state->id . '-extra',
                ])
                . '></span>'
        );
    }

    /**
     * Describe the actual submitted input.
     *
     * @return list<\FormCompose\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        return [new Submission($field->state->name)];
    }

    /**
     * Report the additional DOM identity while the bound field reserves the main one.
     *
     * @return list<string>
     */
    public function inputIds(BoundField $field, Attributes $attributes): array
    {
        return [$field->state->id . '-extra'];
    }
}
