<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Configuration\SummaryConfig;

/** Layout settings for form authoring.
 */
trait FormLayoutMethodsTrait
{
    /** Set errorSummary on this mutable builder. */
    public function errorSummary(bool|SummaryConfig $value = true): static
    {
        $this->updateFormConfig($this->config()->errorSummary($value));

        return $this;
    }

    /** Set submitConfig on this mutable builder. */
    public function submitConfig(ButtonConfig $value): static
    {
        $this->updateFormConfig($this->config()->submitConfig($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FormConfig;

    /** Store a typed configuration update on the attached builder. */
    abstract protected function updateFormConfig(FormConfig $config): void;
}
