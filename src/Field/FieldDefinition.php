<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Html;
use InvalidArgumentException;

/**
 * Reusable field configuration, independent of requests and rendering.
 */
final readonly class FieldDefinition
{
    /**
     * Define a field by its dot-separated context path.
     *
     * @param array<string, mixed> $options Field behavior and presentation options.
     */
    public function __construct(public string $path, public array $options = [])
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*$/D', $path)) {
            throw new InvalidArgumentException('Use a nonempty dot-separated field path.');
        }
    }

    /**
     * Start a fluent field definition, with optional control type inference.
     */
    public static function make(string $path, ?string $type = null): self
    {
        return new self($path, $type === null ? [] : ['type' => $type]);
    }

    /**
     * Return a definition with explicit option overrides.
     *
     * @param array<string, mixed> $options Field options.
     */
    public function with(array $options): self
    {
        return new self($this->path, array_replace($this->options, $options));
    }

    /**
     * Set label content or suppress the label.
     */
    public function label(string|Html|false $text): self
    {
        return $this->with(['label' => $text]);
    }

    /**
     * Set help content, escaped unless explicitly trusted.
     */
    public function help(string|Html $text): self
    {
        return $this->with(['help' => $text]);
    }
}
