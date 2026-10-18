<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FormConfig;

/** Submission settings for form authoring.
 */
trait FormSubmissionMethodsTrait
{
    /** @param array|string|false|null $value A CakePHP route or URL. */
    public function url(array|string|false|null $value): static
    {
        $this->updateFormConfig($this->config()->withUrl($value));

        return $this;
    }

    /** Set method on this mutable builder. */
    public function method(?string $value): static
    {
        $this->updateFormConfig($this->config()->withMethod($value));

        return $this;
    }

    /** Set id on this mutable builder. */
    public function id(?string $value): static
    {
        $this->updateFormConfig($this->config()->withId($value));

        return $this;
    }

    /** Set multipart on this mutable builder. */
    public function multipart(bool $value = true): static
    {
        $this->updateFormConfig($this->config()->withMultipart($value));

        return $this;
    }

    /** Set enctype on this mutable builder. */
    public function enctype(?string $value): static
    {
        $this->updateFormConfig($this->config()->withEnctype($value));

        return $this;
    }

    /** Set encoding on this mutable builder. */

    public function encoding(?string $value): static
    {
        $this->updateFormConfig($this->config()->withEncoding($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FormConfig;

    /** Store a typed configuration update and notify attached behavior. */
    abstract protected function updateFormConfig(FormConfig $config): void;
}
