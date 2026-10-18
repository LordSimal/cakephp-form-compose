<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\FormConfig;

/** Authors the protected submission and presentation shared by action links and buttons. */
trait ActionMethodsTrait
{
    /** Read the current protected action settings. */
    abstract public function config(): ActionConfig;

    /** Capture the next protected action settings. */
    abstract protected function updateActionConfig(ActionConfig $config): void;

    /** Set method on this mutable builder. */
    public function method(string $value): static
    {
        $this->updateActionConfig($this->config()->withMethod($value));

        return $this;
    }

    /** @param array<string, mixed> $value Nested locked action data. */
    public function data(array $value): static
    {
        $this->updateActionConfig($this->config()->withData($value));

        return $this;
    }

    /** Set confirm on this mutable builder. */
    public function confirm(?string $value): static
    {
        $this->updateActionConfig($this->config()->withConfirm($value));

        return $this;
    }

    /** Set block on this mutable builder. */
    public function block(?string $value): static
    {
        $this->updateActionConfig($this->config()->withBlock($value));

        return $this;
    }

    /** Set form on this mutable builder. */
    public function form(FormConfig|FormConfigBuilder $value): static
    {
        $this->updateActionConfig($this->config()->withForm($value));

        return $this;
    }
}
