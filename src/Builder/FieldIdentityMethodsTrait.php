<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FieldConfig;

/** Mutable field identity setters.
 */
trait FieldIdentityMethodsTrait
{
    /** Set type on this mutable builder. */
    public function type(?string $value): static
    {
        $this->updateFieldConfig($this->config()->withType($value));

        return $this;
    }

    /** Set name on this mutable builder. */
    public function name(?string $value): static
    {
        $this->updateFieldConfig($this->config()->withName($value));

        return $this;
    }

    /** Set id on this mutable builder. */
    public function id(?string $value): static
    {
        $this->updateFieldConfig($this->config()->withId($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FieldConfig;

    /** Store a typed configuration update and notify attached behavior. */
    abstract protected function updateFieldConfig(FieldConfig $config): void;
}
