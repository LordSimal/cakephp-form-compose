<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use FormCompose\Builder\ThemeBuilder;
use InvalidArgumentException;

/**
 * Configures ordered template layers and attribute defaults for a design system.
 *
 * PhpRenderer searches each CakePHP element prefix in order, followed by the base
 * elements. Defaults are keyed by part or type.part; type-specific values replace
 * generic values before field and local attributes are merged.
 *
 * ```php
 * $theme = new Theme(
 *     layers: ['forms/application'],
 *     defaults: ['input' => ['class' => 'control']],
 * );
 * $form = $this->Form->form($article)->theme($theme)->bind();
 * ```
 */
final readonly class Theme
{
    /**
     * Compose layers in priority order, followed by the built-in base theme.
     *
     * @param list<string> $layers Cake element prefixes, highest priority first.
     * @param array<string, array<string, mixed>> $defaults Attribute defaults keyed by part or type.part.
     */
    public function __construct(public array $layers = [], public array $defaults = [])
    {
        foreach ($layers as $layer) {
            if ($layer === '' || str_contains($layer, '..')) {
                throw new InvalidArgumentException('Theme layers must be nonempty element prefixes without traversal.');
            }
        }
    }

    /** Author layers and defaults through the fluent theme API. */
    public static function builder(): ThemeBuilder
    {
        return new ThemeBuilder();
    }

    /**
     * Provide a CSS-free base theme.
     */
    public static function base(): self
    {
        return new self();
    }

    /**
     * Resolve generic and type-specific attribute defaults for a part.
     */
    public function attributes(string $part, string $type): Attributes
    {
        $attributes = new Attributes($this->defaults[$part] ?? []);

        return $attributes->replace($this->defaults[$type . '.' . $part] ?? []);
    }
}
