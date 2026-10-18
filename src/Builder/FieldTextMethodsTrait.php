<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\Translated;
use FormCompose\Rendering\Html;

/** Mutable field text setters.
 */
trait FieldTextMethodsTrait
{
    /** Set label on this mutable builder. */
    public function label(string|Html|Translated|Closure|false|null $value): static
    {
        $this->updateFieldConfig($this->config()->withLabel($value));

        return $this;
    }

    /** Set help on this mutable builder. */
    public function help(string|Html|Translated|Closure|false|null $value): static
    {
        $this->updateFieldConfig($this->config()->withHelp($value));

        return $this;
    }

    /** Set errors on this mutable builder. */
    public function errors(bool $value = true): static
    {
        $this->updateFieldConfig($this->config()->withErrors($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FieldConfig;

    /** Store a typed configuration update and notify attached behavior. */
    abstract protected function updateFieldConfig(FieldConfig $config): void;
}
