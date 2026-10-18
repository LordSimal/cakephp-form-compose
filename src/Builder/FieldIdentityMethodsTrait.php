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
        $this->updateFieldConfig($this->config()->type($value));

        return $this;
    }

    /** Set name on this mutable builder. */
    public function name(?string $value): static
    {
        $this->updateFieldConfig($this->config()->name($value));

        return $this;
    }

    /** Set id on this mutable builder. */
    public function id(?string $value): static
    {
        $this->updateFieldConfig($this->config()->id($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FieldConfig;

    /** Store a typed configuration update on the attached builder. */
    abstract protected function updateFieldConfig(FieldConfig $config): void;
}
