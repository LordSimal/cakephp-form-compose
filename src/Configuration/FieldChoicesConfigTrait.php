<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use BackedEnum;
use InvalidArgumentException;

/** Typed choices settings shared by immutable field configuration.
 * @require-extends \FormCompose\Configuration\Configuration
 */
trait FieldChoicesConfigTrait
{
    /** The configured choices setting. */
    public iterable|string|null $choices {
        get {
            /** @var mixed $value */
            $value = $this->values['choices'] ?? null;
            assert(
                $value === null || is_iterable($value) || is_string($value),
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** The configured placeholder setting. */
    public bool|string|null $placeholder {
        get {
            /** @var mixed $value */
            $value = $this->values['placeholder'] ?? null;
            assert(
                $value === null || is_bool($value) || is_string($value),
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** The configured checkedValue setting. */
    public mixed $checkedValue {
        get {
            return $this->values['checkedValue'] ?? '1';
        }
    }

    /** The configured uncheckedValue setting. */
    public mixed $uncheckedValue {
        get {
            return $this->values['uncheckedValue'] ?? '0';
        }
    }

    /** The configured fallback setting. */
    public bool $fallback {
        get {
            /** @var mixed $value */
            $value = $this->values['fallback'] ?? true;
            assert(is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit choices setting. */
    /** @param iterable<mixed, mixed>|string|null $value Choice maps, lazy sources, or an enum class. */
    public function withChoices(iterable|string|null $value): static
    {
        if (is_string($value) && !is_subclass_of($value, BackedEnum::class)) {
            throw new InvalidArgumentException('Choices must use a backed enum class name or an iterable.');
        }

        return $this->changed('choices', is_iterable($value) ? ChoiceList::capture($value) : $value);
    }

    /** Return configuration with an explicit placeholder setting. */
    public function withPlaceholder(bool|string|null $value): static
    {
        return $this->changed('placeholder', $value);
    }

    /** Return configuration with an explicit checkedValue setting. */
    public function withCheckedValue(mixed $value): static
    {
        return $this->changed('checkedValue', $value);
    }

    /** Return configuration with an explicit uncheckedValue setting. */
    public function withUncheckedValue(mixed $value): static
    {
        return $this->changed('uncheckedValue', $value);
    }

    /** Return configuration with an explicit fallback setting. */
    public function withFallback(bool $value): static
    {
        return $this->changed('fallback', $value);
    }
}
