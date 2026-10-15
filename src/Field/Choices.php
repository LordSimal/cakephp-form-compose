<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\Theme;

/**
 * Turns an iterable of option values and labels into a tree of Choice records.
 *
 * Used by NativeType for selects, radios, and checkbox groups. Supports simple
 * value/label pairs, nested groups, and extended options with value, text, and
 * attributes. Assigns unique choice IDs and prepares label and wrapper defaults
 * from the theme before passing the records to templates. Override
 * createChoiceAttributes() to supply custom per-option attribute rules.
 */
class Choices
{
    /** @var array<string, true> */
    protected array $ids = [];

    /**
     * Use the selected theme's defaults for options, labels, and choice groups.
     */
    public function __construct(protected readonly Theme $theme)
    {
    }

    /**
     * Preserve nested groups, extended options, and per-choice metadata.
     *
     * @param iterable<array-key, mixed> $options Native choice options.
     * @return list<\FormCompose\Field\Choice>
     */
    public function resolve(FieldState $state, Attributes $attributes, iterable $options): array
    {
        $choices = [];
        /** @var mixed $label */
        foreach ($options as $value => $label) {
            if (is_array($label) && !array_key_exists('value', $label)) {
                $choices[] = new Choice(
                    'group',
                    (string)$value,
                    $this->theme->attributes('choice-group', $state->type),
                    $this->resolve($state, $attributes, $label),
                );
                continue;
            }

            /** @var array<string, mixed> $option */
            $option = is_array($label) ? $label : ['value' => $value, 'text' => $label];
            $id = (string)($option['id'] ?? $state->id . '-' . rawurlencode(NativeValue::scalar($option['value'])));
            $base = $id;
            $suffix = 0;
            while (isset($this->ids[$id])) {
                $id = $base . '-' . ++$suffix;
            }

            $this->ids[$id] = true;
            $option['id'] = $id;
            /** @var mixed $text */
            $text = $option['text'] ?? '';
            $choices[] = new Choice(
                'item',
                $text instanceof Html ? $text : (string)$text,
                $this->createChoiceAttributes()->resolve($state, $attributes, $option),
                [],
                [
                    'label' => $this->theme->attributes('choice-label', $state->type)->replace(['for' => $id]),
                    'container' => $this->theme->attributes('choice-container', $state->type),
                ],
            );
        }

        return $choices;
    }

    /**
     * Create the attribute resolver used for each option in this choice tree.
     */
    protected function createChoiceAttributes(): ChoiceAttributes
    {
        return new ChoiceAttributes($this->theme);
    }
}
