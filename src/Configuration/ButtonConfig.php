<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Immutable button configuration captured before binding or rendering. */
final class ButtonConfig extends Configuration
{
    use ButtonStateTrait;

    /** The configured type setting. */
    public string $type {
        get {
            /** @var mixed $value */
            $value = $this->values['type'] ?? 'submit';
            assert(is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit type setting. */
    public function type(string $value): static
    {
        return $this->changed('type', $value);
    }

    /** Initialize common named settings without losing explicit null values. */
    public function __construct(
        string|Unspecified $type = Unspecified::Value,
        string|Unspecified|null $name = Unspecified::Value,
        mixed $value = Unspecified::Value,
    ) {
        parent::__construct(array_filter(
            ['type' => $type, 'name' => $name, 'value' => $value],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        ));
    }

    /** Start an immutable fluent configuration; every setting returns a new instance. */
    public static function builder(): self
    {
        return new self();
    }
}
