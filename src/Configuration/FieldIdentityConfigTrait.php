<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Typed identity settings shared by immutable field configuration.
 * @require-extends \FormCompose\Configuration\Configuration
 */
trait FieldIdentityConfigTrait
{
    /** The configured type setting. */
    public ?string $type {
        get {
            /** @var mixed $value */
            $value = $this->values['type'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** The configured name setting. */
    public ?string $name {
        get {
            /** @var mixed $value */
            $value = $this->values['name'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** The configured id setting. */
    public ?string $id {
        get {
            /** @var mixed $value */
            $value = $this->values['id'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit type setting. */
    public function type(?string $value): static
    {
        return $this->changed('type', $value);
    }

    /** Return configuration with an explicit name setting. */
    public function name(?string $value): static
    {
        return $this->changed('name', $value);
    }

    /** Return configuration with an explicit id setting. */
    public function id(?string $value): static
    {
        return $this->changed('id', $value);
    }
}
