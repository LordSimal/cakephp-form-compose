<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use InvalidArgumentException;

/**
 * Normalizes template shorthand into the renderer's part-to-element map.
 *
 * Fields and blueprints select the control part; FormConstruction selects layout
 * for complete forms. The templates option selects named parts. Normalizing once
 * lets PHP and custom renderers consume the same options, and rejects conflicting
 * selections before any HTML is rendered.
 */
final class TemplateOptions
{
    /**
     * Expand template to the selected primary part, preserving other part overrides.
     *
     * @param array<string, mixed> $options Merged definition and per-call options.
     * @param string $part Primary part: control for fields/blueprints, layout for complete forms.
     * @return array<string, mixed>
     * @throws \InvalidArgumentException When the shorthand is invalid or conflicts with the selected part override.
     */
    public static function normalize(array $options, string $part = 'control'): array
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
        if (array_key_exists($part, $templates) && $templates[$part] !== $template) {
            throw new InvalidArgumentException('The template option conflicts with templates[' . $part . '].');
        }

        $options['templates'] = array_replace($templates, [$part => $template]);
        unset($options['template']);

        return $options;
    }
}
