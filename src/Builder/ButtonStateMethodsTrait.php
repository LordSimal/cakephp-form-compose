<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\ButtonConfig;

/** Authors native button state consistently for ordinary and protected action buttons. */
trait ButtonStateMethodsTrait
{
    /** Read native button state from its configuration domain. */
    abstract protected function buttonConfig(): ButtonConfig|ActionConfig;

    /** Store native button state in its configuration domain. */
    abstract protected function updateButtonConfig(ButtonConfig|ActionConfig $config): void;

    /** Set the optional submitted button name. */
    public function name(?string $value): static
    {
        $this->updateButtonConfig($this->buttonConfig()->name($value));

        return $this;
    }

    /** Set the submitted button value, preserving false, zero, and null. */
    public function value(mixed $value): static
    {
        $this->updateButtonConfig($this->buttonConfig()->value($value));

        return $this;
    }

    /** Enable or disable the native button. */
    public function disabled(bool $value = true): static
    {
        $this->updateButtonConfig($this->buttonConfig()->disabled($value));

        return $this;
    }
}
