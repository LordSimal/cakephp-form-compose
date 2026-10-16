<?php
declare(strict_types=1);

namespace FormCompose\Form;

use FormCompose\Binding\FieldPath;
use FormCompose\Field\BoundField;
use FormCompose\Field\ErrorTargetProviderInterface;
use FormCompose\Field\FieldTypeInterface;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Html;
use InvalidArgumentException;

/**
 * Captures rendered validation feedback without retaining a bound field or security service.
 */
final readonly class FeedbackEntry
{
    /**
     * Capture a display label, ordered Cake messages, and an optional emitted control ID.
     *
     * @param list<string> $messages Existing validation messages, always escaped when rendered.
     */
    public function __construct(public string|Html $label, public array $messages, public ?string $target = null)
    {
        if ($target === '') {
            throw new InvalidArgumentException('Error summary targets must be nonempty IDs or null.');
        }
    }

    /**
     * Describe eligible rendered fields without changing their inline feedback behavior.
     */
    public static function field(BoundField $field, FieldTypeInterface $type, Attributes $attributes): ?self
    {
        if (!$field->state->hasErrors()) {
            return null;
        }

        /** @var string|Html|false $label */
        $label = $field->state->options['label'] ?? FieldPath::label($field->state->path);
        $target = match (true) {
            $attributes->has('disabled') || $field->state->type === 'hidden' => null,
            $type instanceof ErrorTargetProviderInterface => $type->errorTarget($field, $attributes),
            default => $field->state->id,
        };

        return new self(
            $label === false || $label === '' ? FieldPath::label($field->state->path) : $label,
            $field->state->messages,
            $target,
        );
    }
}
