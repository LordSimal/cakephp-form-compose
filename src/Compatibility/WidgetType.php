<?php
declare(strict_types=1);

namespace FormCompose\Compatibility;

use Cake\View\Form\ContextInterface;
use Cake\View\Widget\WidgetInterface;
use FormCompose\Field\BoundField;
use FormCompose\Field\FieldTypeInterface;
use FormCompose\Field\Submission;
use FormCompose\Rendering\Attributes;

/**
 * Explicit optional adapter for existing widget instances; never constructs FormHelper.
 */
final readonly class WidgetType implements FieldTypeInterface
{
    /**
     * Accept an existing widget and its required context at the compatibility boundary.
     */
    public function __construct(private WidgetInterface $widget, private ContextInterface $context)
    {
    }

    /**
     * Delegate only this explicitly selected compatibility control to its widget.
     */
    public function render(BoundField $field, Attributes $attributes): string
    {
        return $this->widget->render(
            $attributes->values + ['val' => $field->state->value, 'escape' => true],
            $this->context,
        );
    }

    /**
     * Preserve the existing widget's submission description and explicit unlocking policy.
     *
     * @return list<\FormCompose\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        if ($attributes->values['disabled'] ?? false) {
            return [];
        }
        $names = $this->widget->secureFields($attributes->values + ['val' => $field->state->value]);

        return array_map(
            static fn(string $name): Submission => new Submission(
                $name,
                ($field->state->options['secure'] ?? true) !== false,
            ),
            array_values($names),
        );
    }
}
