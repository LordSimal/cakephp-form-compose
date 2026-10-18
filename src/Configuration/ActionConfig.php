<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Immutable action configuration captured before binding or rendering. */
final class ActionConfig extends Configuration
{
    use ButtonStateTrait;

    /** The configured method setting. */
    public ?string $method {
        get {
            /** @var mixed $value */
            $value = $this->values['method'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit method setting. */
    public function method(string $value): static
    {
        return $this->changed('method', $value);
    }

    /** @var array<string, mixed> Nested locked action data. */
    public array $data {
        get {
            /** @var mixed $value */
            $value = $this->values['data'] ?? [];
            assert(is_array($value), 'Typed configuration must retain its declared value type.');

            /** @var array<string, mixed> $value */

            return $value;
        }
    }

    /** @param array<string, mixed> $value Nested locked action data. */
    public function data(array $value): static
    {
        return $this->changed('data', $value);
    }

    /** The configured confirm setting. */
    public ?string $confirm {
        get {
            /** @var mixed $value */
            $value = $this->values['confirm'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit confirm setting. */
    public function confirm(?string $value): static
    {
        return $this->changed('confirm', $value);
    }

    /** The configured block setting. */
    public ?string $block {
        get {
            /** @var mixed $value */
            $value = $this->values['block'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit block setting. */
    public function block(?string $value): static
    {
        return $this->changed('block', $value);
    }

    /** The configured form setting. */
    public FormConfig $form {
        get {
            /** @var mixed $value */
            $value = $this->values['form'] ?? new FormConfig();
            assert($value instanceof FormConfig, 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit form setting. */
    public function form(FormConfig $value): static
    {
        return $this->changed('form', $value);
    }

    /** Initialize common named settings without losing explicit null values. */
    public function __construct(
        string|Unspecified $method = Unspecified::Value,
        array|Unspecified $data = Unspecified::Value,
        string|Unspecified|null $confirm = Unspecified::Value,
        string|Unspecified|null $block = Unspecified::Value,
    ) {
        parent::__construct(array_filter(
            ['method' => $method, 'data' => $data, 'confirm' => $confirm, 'block' => $block],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        ));
    }

    /** Start an immutable fluent configuration; every setting returns a new instance. */
    public static function builder(): self
    {
        return new self();
    }
}
