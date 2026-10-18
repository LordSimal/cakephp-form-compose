<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use DateTimeZone;

/** Typed value settings shared by immutable field configuration.
 * @require-extends \FormCompose\Configuration\Configuration
 */
trait FieldValueConfigTrait
{
    /** The configured value setting. */
    public mixed $value {
        get {
            return $this->values['value'] ?? null;
        }
    }

    /** The configured defaultValue setting. */
    public mixed $defaultValue {
        get {
            return $this->values['defaultValue'] ?? null;
        }
    }

    /** The configured schemaDefault setting. */
    public bool $schemaDefault {
        get {
            /** @var mixed $value */
            $value = $this->values['schemaDefault'] ?? true;
            assert(is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** The configured timezone setting. */
    public DateTimeZone|string|null $timezone {
        get {
            /** @var mixed $value */
            $value = $this->values['timezone'] ?? null;
            assert(
                $value === null || is_string($value) || $value instanceof DateTimeZone,
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** Return configuration with an explicit value setting. */
    public function withValue(mixed $value): static
    {
        return $this->changed('value', $value);
    }

    /** Return configuration with an explicit defaultValue setting. */
    public function withDefaultValue(mixed $value): static
    {
        return $this->changed('defaultValue', $value);
    }

    /** Return configuration with an explicit schemaDefault setting. */
    public function withSchemaDefault(bool $value): static
    {
        return $this->changed('schemaDefault', $value);
    }

    /** Return configuration with an explicit timezone setting. */
    public function withTimezone(DateTimeZone|string|null $value): static
    {
        return $this->changed('timezone', $value);
    }
}
