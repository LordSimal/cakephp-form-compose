<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/**
 * Shares typed submission state between ordinary buttons and protected action buttons.
 * @require-extends Configuration
 */
trait ButtonStateTrait
{
    /** The configured name setting. */
    public ?string $name {
        get {
            /** @var mixed $value */
            $value = $this->values['name'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit name setting. */
    public function name(?string $value): static
    {
        return $this->changed('name', $value);
    }

    /** The configured value setting. */
    public mixed $value {
        get {
            return $this->values['value'] ?? null;
        }
    }

    /** Return configuration with an explicit value setting. */
    public function value(mixed $value): static
    {
        return $this->changed('value', $value);
    }

    /** The configured disabled setting. */
    public bool $disabled {
        get {
            /** @var mixed $value */
            $value = $this->values['disabled'] ?? false;
            assert(is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit disabled setting. */
    public function disabled(bool $value = true): static
    {
        return $this->changed('disabled', $value);
    }
}
