<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Summary;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\ErrorTargetProviderInterface;
use FormCompose\Form\Field\FieldTypeInterface;
use FormCompose\Form\Field\InputIdentityProviderInterface;
use FormCompose\Form\Field\Submission;
use FormCompose\Rendering\Attributes;

/**
 * Supplies an optional composite target through explicit and discovered registrations.
 */
#[AsFieldType('summary-range')]
class RangeType implements FieldTypeInterface, InputIdentityProviderInterface, ErrorTargetProviderInterface
{
    /**
     * Allow tests and applications to opt out of a link or reject an invalid target.
     */
    public function __construct(protected readonly ?string $target = '-end')
    {
    }

    public function render(BoundField $field, Attributes $attributes): string
    {
        return (
            '<input'
                . (string)$attributes
                . '><input'
                . (string)$attributes->replace([
                    'id' => $field->state->id . '-end',
                    'name' => $field->state->name . '_end',
                ])
                . '>'
        );
    }

    /** @return list<\FormCompose\Form\Field\Submission> */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        return (
            $attributes->has('disabled')
                ? []
                : [new Submission($field->state->name), new Submission($field->state->name . '_end')]
        );
    }

    /** @return list<string> */
    public function inputIds(BoundField $field, Attributes $attributes): array
    {
        return [$field->state->id . '-end'];
    }

    public function errorTarget(BoundField $field, Attributes $attributes): ?string
    {
        return match ($this->target) {
            null => null,
            '' => '',
            default => $field->state->id . $this->target,
        };
    }
}
