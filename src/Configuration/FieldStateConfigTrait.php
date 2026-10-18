<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Typed state settings shared by immutable field configuration.
 * @require-extends \FormCompose\Configuration\Configuration
 */
trait FieldStateConfigTrait
{
    /** The configured required setting. */
    public ?bool $required {
        get {
            /** @var mixed $value */
            $value = $this->values['required'] ?? null;
            assert($value === null || is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
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

    /** The configured readonly setting. */
    public bool $readonly {
        get {
            /** @var mixed $value */
            $value = $this->values['readonly'] ?? false;
            assert(is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** The configured multiple setting. */
    public ?bool $multiple {
        get {
            /** @var mixed $value */
            $value = $this->values['multiple'] ?? null;
            assert($value === null || is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** The configured secure setting. */
    public bool $secure {
        get {
            /** @var mixed $value */
            $value = $this->values['secure'] ?? true;
            assert(is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit required setting. */
    public function withRequired(?bool $value): static
    {
        return $this->changed('required', $value);
    }

    /** Return configuration with an explicit disabled setting. */
    public function withDisabled(bool $value): static
    {
        return $this->changed('disabled', $value);
    }

    /** Return configuration with an explicit readonly setting. */
    public function withReadonly(bool $value): static
    {
        return $this->changed('readonly', $value);
    }

    /** Return configuration with an explicit multiple setting. */
    public function withMultiple(?bool $value): static
    {
        return $this->changed('multiple', $value);
    }

    /** Return configuration with an explicit secure setting. */
    public function withSecure(bool $value): static
    {
        return $this->changed('secure', $value);
    }
}
