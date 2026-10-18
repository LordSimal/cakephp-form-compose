<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use DateTimeZone;
use FormCompose\Configuration\FieldConfig;

/** Mutable field value setters.
 */
trait FieldValueMethodsTrait
{
    /** Set value on this mutable builder. */
    public function value(mixed $value): static
    {
        $this->updateFieldConfig($this->config()->value($value));

        return $this;
    }

    /** Set defaultValue on this mutable builder. */
    public function default(mixed $value): static
    {
        $this->updateFieldConfig($this->config()->default($value));

        return $this;
    }

    /** Set schemaDefault on this mutable builder. */
    public function schemaDefault(bool $value = true): static
    {
        $this->updateFieldConfig($this->config()->schemaDefault($value));

        return $this;
    }

    /** Set timezone on this mutable builder. */
    public function timezone(DateTimeZone|string|null $value): static
    {
        $this->updateFieldConfig($this->config()->timezone($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FieldConfig;

    /** Store a typed configuration update on the attached builder. */
    abstract protected function updateFieldConfig(FieldConfig $config): void;
}
