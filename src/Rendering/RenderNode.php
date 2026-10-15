<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use FormCompose\Field\BoundField;

/**
 * A typed rendering request scoped to fields, forms, or buttons.
 */
final readonly class RenderNode
{
    /**
     * Capture template variables and explicit presentation overrides.
     *
     * @param array<string, mixed> $variables Variables made available to PHP elements.
     * @param array<string, mixed> $options Optional templates and variant configuration.
     */
    public function __construct(
        public string $scope,
        public string $type,
        public array $variables,
        public array $options = [],
    ) {
    }

    /**
     * Create a field request shared by outer parts and native control parts.
     *
     * @param array<string, mixed> $variables Additional native control variables.
     */
    public static function field(BoundField $field, Attributes $attributes, array $variables = []): self
    {
        return new self(
            'field',
            $field->state->type,
            ['field' => $field, 'attributes' => $attributes] + $variables,
            $field->state->options,
        );
    }
}
