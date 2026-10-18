<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Configuration\SummaryConfig;
use FormCompose\Rendering\Html;

/** Layout settings for form authoring.
 */
trait FormLayoutMethodsTrait
{
    /** Set errorSummary on this mutable builder. */
    public function errorSummary(bool|SummaryConfig|SummaryConfigBuilder $value = true): static
    {
        $this->updateFormConfig($this->config()->withErrorSummary($value));

        return $this;
    }

    /** Set submit on this mutable builder. */

    public function submit(string|Html|false|null $value): static
    {
        $this->updateFormConfig($this->config()->withSubmit($value));

        return $this;
    }

    /** Set submitConfig on this mutable builder. */
    public function submitConfig(ButtonConfig|ButtonConfigBuilder $value): static
    {
        $this->updateFormConfig($this->config()->withSubmitConfig($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FormConfig;

    /** Store a typed configuration update and notify attached behavior. */
    abstract protected function updateFormConfig(FormConfig $config): void;
}
