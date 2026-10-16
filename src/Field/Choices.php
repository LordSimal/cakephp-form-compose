<?php
declare(strict_types=1);

namespace FormCompose\Field;

use BackedEnum;
use Cake\Database\Type\EnumLabelInterface;
use Cake\Utility\Inflector;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\Theme;
use InvalidArgumentException;
use ReflectionEnum;
use ReflectionEnumUnitCase;
use UnitEnum;

/**
 * Turns native choice options and backed enums into a tree of Choice records.
 *
 * Used by NativeType for selects, radios, and checkbox groups. Supports simple
 * value/label pairs, backed enum classes or cases, nested groups, and entries
 * with value, text, and attributes. Assigns unique choice IDs and prepares label
 * and wrapper defaults from the theme before passing the records to templates. Override
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
     * @param iterable<array-key, mixed>|string $options Native choices or a backed enum class name.
     * @return list<\FormCompose\Field\Choice>
     */
    public function resolve(FieldState $state, Attributes $attributes, iterable|string $options): array
    {
        if (is_string($options)) {
            if (!enum_exists($options) || !is_subclass_of($options, BackedEnum::class)) {
                throw new InvalidArgumentException('Choice enum classes must be backed enums.');
            }

            $options = array_map(
                static fn(ReflectionEnumUnitCase $case): UnitEnum => $case->getValue(),
                new ReflectionEnum($options)->getCases(),
            );
        }

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

            $option = $this->option($value, $label);
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
     * Expand enum cases without replacing explicitly supplied choice text or metadata.
     *
     * @return array<string, mixed> Choice data containing value and optional text/attributes.
     */
    protected function option(int|string $value, mixed $label): array
    {
        /** @var array<string, mixed> $option */
        $option = match (true) {
            is_array($label) => $label,
            $label instanceof UnitEnum => ['value' => $label],
            default => ['value' => $value, 'text' => $label],
        };
        if ($option['value'] instanceof UnitEnum) {
            if (!$option['value'] instanceof BackedEnum) {
                throw new InvalidArgumentException('Choice enum cases must be backed enums.');
            }

            if (!array_key_exists('text', $option)) {
                $option['text'] = $this->enumLabel($option['value']);
            }
        }

        return $option;
    }

    /**
     * Follow CakePHP's enum label contract; applications own translation of labels.
     */
    protected function enumLabel(BackedEnum $case): string
    {
        return $case instanceof EnumLabelInterface
            ? $case->label()
            : Inflector::humanize(Inflector::underscore($case->name));
    }

    /**
     * Create the attribute resolver used for each option in this choice tree.
     */
    protected function createChoiceAttributes(): ChoiceAttributes
    {
        return new ChoiceAttributes($this->theme);
    }
}
