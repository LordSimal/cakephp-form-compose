<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Submission settings for form configuration.
 * @require-extends \FormCompose\Configuration\Configuration
 */
trait FormSubmissionSettingsTrait
{
    /** @var array|string|false|null A CakePHP route or URL. */
    public array|string|false|null $url {
        get {
            /** @var mixed $value */
            $value = $this->values['url'] ?? null;
            assert(
                $value === null || $value === false || is_array($value) || is_string($value),
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** @param array|string|false|null $value A CakePHP route or URL. */
    public function withUrl(array|string|false|null $value): static
    {
        return $this->changed('url', $value);
    }

    /** The configured method setting. */
    public ?string $method {
        get {
            /** @var mixed $value */
            $value = $this->values['method'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit method setting. */
    public function withMethod(?string $value): static
    {
        return $this->changed('method', $value);
    }

    /** The configured id setting. */
    public ?string $id {
        get {
            /** @var mixed $value */
            $value = $this->values['id'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit id setting. */
    public function withId(?string $value): static
    {
        return $this->changed('id', $value);
    }

    /** The configured multipart setting. */
    public bool $multipart {
        get {
            /** @var mixed $value */
            $value = $this->values['multipart'] ?? false;
            assert(is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit multipart setting. */
    public function withMultipart(bool $value): static
    {
        return $this->changed('multipart', $value);
    }

    /** The configured enctype setting. */
    public ?string $enctype {
        get {
            /** @var mixed $value */
            $value = $this->values['enctype'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit enctype setting. */
    public function withEnctype(?string $value): static
    {
        return $this->changed('enctype', $value);
    }

    /** The configured encoding setting. */

    public ?string $encoding {
        get {
            /** @var mixed $value */
            $value = $this->values['encoding'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit encoding setting. */
    public function withEncoding(?string $value): static
    {
        return $this->changed('encoding', $value);
    }
}
