<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\RenderableInterface;

/** Configures a form before binding; every bind captures an independent session. */
class FormBuilder extends FormConfigBuilder implements RenderableInterface
{
    /** @param \Closure(FormConfig): FormSession $bind Session factory preserving helper extension points. */
    public function __construct(
        protected readonly Closure $bind,
        FormConfig|FormConfigBuilder $config = new FormConfig(),
    ) {
        parent::__construct($config);
    }

    /** Bind data, blueprint defaults, and presentation into an isolated form lifecycle. */
    public function bind(): FormSession
    {
        return ($this->bind)($this->config());
    }

    /** Select the complete form's default action. */
    public function submit(string|Html|false|null $value, ButtonConfig|ButtonConfigBuilder|null $config = null): static
    {
        parent::submit($value);
        if ($config !== null) {
            $this->submitConfig($config);
        }

        return $this;
    }

    /** Omit the complete form's default action. */
    public function withoutSubmit(): static
    {
        return $this->submit(false);
    }

    /** Bind a fresh session and render its complete layout. */
    public function render(): string
    {
        return $this->bind()->render();
    }

    /** Render a complete form at the point of string conversion. */
    public function __toString(): string
    {
        return $this->render();
    }
}
