<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Theme;

/**
 * Resolves the attributes of one option within a choice control.
 *
 * Choices supplies the field value, common input attributes, and option-specific
 * data. This class computes selected or checked status and merges theme defaults
 * while preserving the input name, control type, and a disabled parent state.
 */
class ChoiceAttributes
{
    /**
     * Use theme defaults when preparing individual choice attributes.
     */
    public function __construct(protected readonly Theme $theme)
    {
    }

    /**
     * Preserve bound selection and identity while allowing option-specific presentation.
     *
     * @param array<string, mixed> $option Option data with value, text, and optional attributes.
     */
    public function resolve(FieldState $state, Attributes $common, array $option): Attributes
    {
        $value = NativeValue::scalar($option['value']);
        $selected = $this->selected($state, $value);
        $extra = array_diff_key($option, array_flip(['value', 'text', 'name', 'type']));
        if ($state->type === 'select') {
            return $this->theme
                ->attributes('option', $state->type)
                ->merge($extra)
                ->replace([
                    'value' => $value,
                    'selected' => $selected ? 'selected' : false,
                ]);
        }

        $input = $common->merge($extra)
            ->replace([
                'type' => $state->type === 'radio' ? 'radio' : 'checkbox',
                'name' => $common->values['name'],
                'value' => $value,
                'checked' => $selected ? 'checked' : false,
                'multiple' => null,
            ]);
        if ($common->has('disabled')) {
            $input = $input->replace(['disabled' => true]);
        }

        return $this->theme->attributes('choice', $state->type)->merge($input->values);
    }

    /**
     * Match scalar or multiple values without conflating null with an empty selection.
     */
    protected function selected(FieldState $state, string $value): bool
    {
        if ($state->value === null) {
            return false;
        }

        $values = is_array($state->value) ? $state->value : [$state->value];

        return in_array($value, array_map(NativeValue::scalar(...), $values), true);
    }
}
