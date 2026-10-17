<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use FormCompose\Form\Field\BoundField;
use InvalidArgumentException;

/**
 * Carries prepared template data and lookup options to a RendererInterface.
 *
 * scope distinguishes field, form, button, and blueprint parts. type selects a control type
 * or effective method; variables become template locals. options supplies variant
 * and explicit template choices. Every node exposes a templateData array in its
 * variables, preserving custom PHP values without replacing prepared variables.
 * The part name is passed separately to render().
 *
 * ```php
 * $node = RenderNode::field($field, $field->attributes('label'));
 * $html = $renderer->render('label', $node);
 * ```
 */
final readonly class RenderNode
{
    /** @var array<string, mixed> Prepared template locals, including the templateData bag. */
    public array $variables;

    /**
     * Capture template variables and explicit presentation overrides.
     *
     * @param array<string, mixed> $variables Variables made available to PHP elements.
     * @param array<string, mixed> $options Optional templates, variant, and templateData configuration.
     * @throws \InvalidArgumentException When templateData is not an array.
     */
    public function __construct(
        public string $scope,
        public string $type,
        array $variables,
        public array $options = [],
    ) {
        /** @var mixed $templateData */
        $templateData = array_key_exists('templateData', $options) ? $options['templateData'] : [];
        if (!is_array($templateData)) {
            throw new InvalidArgumentException('The templateData option must be an array.');
        }

        $this->variables = array_replace($variables, ['templateData' => $templateData]);
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
