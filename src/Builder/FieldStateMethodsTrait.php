<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FieldConfig;

/** Mutable field state setters.
 */
trait FieldStateMethodsTrait
{
    /** Set required on this mutable builder. */
    public function required(?bool $value = true): static
    {
        $this->updateFieldConfig($this->config()->withRequired($value));

        return $this;
    }

    /** Set disabled on this mutable builder. */
    public function disabled(bool $value = true): static
    {
        $this->updateFieldConfig($this->config()->withDisabled($value));

        return $this;
    }

    /** Set readonly on this mutable builder. */
    public function readonly(bool $value = true): static
    {
        $this->updateFieldConfig($this->config()->withReadonly($value));

        return $this;
    }

    /** Set multiple on this mutable builder. */
    public function multiple(?bool $value = true): static
    {
        $this->updateFieldConfig($this->config()->withMultiple($value));

        return $this;
    }

    /** Set secure on this mutable builder. */
    public function secure(bool $value = true): static
    {
        $this->updateFieldConfig($this->config()->withSecure($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FieldConfig;

    /** Store a typed configuration update and notify attached behavior. */
    abstract protected function updateFieldConfig(FieldConfig $config): void;
}
