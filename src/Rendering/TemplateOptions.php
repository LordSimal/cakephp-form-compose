<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use InvalidArgumentException;

/**
 * Normalizes control and blueprint layout selection into the renderer's part-to-element map.
 *
 * FieldResolver and BlueprintRenderer call this after merging configuration. The
 * singular template option selects the complete control or layout; templates selects named
 * parts. Normalizing once lets both PhpRenderer and custom renderers consume the
 * same options, and rejects conflicting control choices before any HTML is rendered.
 */
final class TemplateOptions
{
    /**
     * Expand template to templates['control'], preserving other part overrides.
     *
     * @param array<string, mixed> $options Merged definition and per-call options.
     * @return array<string, mixed>
     * @throws \InvalidArgumentException When the shorthand is invalid or conflicts with templates['control'].
     */
    public static function normalize(array $options): array
    {
        if (!array_key_exists('template', $options)) {
            return $options;
        }

        /** @var mixed $template */
        $template = $options['template'];
        if (!is_string($template) || $template === '') {
            throw new InvalidArgumentException('The template option must be a nonempty element name.');
        }

        /** @var array<string, string> $templates */
        $templates = $options['templates'] ?? [];
        if (array_key_exists('control', $templates) && $templates['control'] !== $template) {
            throw new InvalidArgumentException('The template option conflicts with templates[control].');
        }

        $options['templates'] = array_replace($templates, ['control' => $template]);
        unset($options['template']);

        return $options;
    }
}
