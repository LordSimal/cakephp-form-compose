<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FieldConfig;

/** Mutable field choices setters.
 */
trait FieldChoicesMethodsTrait
{
    /** Set choices on this mutable builder. */
    /** @param iterable<mixed, mixed>|string|null $value Choice maps, lazy sources, or an enum class. */
    public function choices(iterable|string|null $value): static
    {
        $this->updateFieldConfig($this->config()->choices($value));

        return $this;
    }

    /** Set placeholder on this mutable builder. */
    public function placeholder(bool|string|null $value): static
    {
        $this->updateFieldConfig($this->config()->placeholder($value));

        return $this;
    }

    /** Set checkedValue on this mutable builder. */
    public function checkedValue(mixed $value): static
    {
        $this->updateFieldConfig($this->config()->checkedValue($value));

        return $this;
    }

    /** Set uncheckedValue on this mutable builder. */
    public function uncheckedValue(mixed $value): static
    {
        $this->updateFieldConfig($this->config()->uncheckedValue($value));

        return $this;
    }

    /** Set fallback on this mutable builder. */
    public function fallback(bool $value = true): static
    {
        $this->updateFieldConfig($this->config()->fallback($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FieldConfig;

    /** Store a typed configuration update on the attached builder. */
    abstract protected function updateFieldConfig(FieldConfig $config): void;
}
