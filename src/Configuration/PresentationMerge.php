<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Rendering\Attributes;

/** Combines canonical presentation layers without losing explicit replacement instructions. */
final class PresentationMerge
{
    /**
     * @param array{attributes?: array<string, mixed>, partAttributes?: array<string, array<string, mixed>>, templates?: array<string, string>, templateData?: array<string, mixed>, variant?: ?string} $left Existing layer.
     * @param array{attributes?: array<string, mixed>, partAttributes?: array<string, array<string, mixed>>, templates?: array<string, string>, templateData?: array<string, mixed>, variant?: ?string} $right Explicit overlay.
     * @param array<string, true> $replace Replaced bag identities.
     * @return array{attributes?: array<string, mixed>, partAttributes?: array<string, array<string, mixed>>, templates?: array<string, string>, templateData?: array<string, mixed>, variant?: ?string}
     */
    public static function values(array $left, array $right, array $replace): array
    {
        $values = $left;
        foreach ($right as $name => $value) {
            if ($name === 'partAttributes') {
                foreach ($right['partAttributes'] ?? [] as $part => $attributes) {
                    $values['partAttributes'][$part] = isset($replace['part:' . $part])
                        ? $attributes
                        : new Attributes(($left['partAttributes'] ?? [])[$part] ?? [])->merge($attributes)->values;
                }

                continue;
            }

            if (!isset($replace[$name]) && in_array($name, ['attributes', 'templates', 'templateData'], true)) {
                $value = match ($name) {
                    'attributes' => new Attributes($left['attributes'] ?? [])->merge(
                        $right['attributes'] ?? [],
                    )->values,
                    'templates' => array_replace($left['templates'] ?? [], $right['templates'] ?? []),
                    default => array_replace($left['templateData'] ?? [], $right['templateData'] ?? []),
                };
            }

            $values[$name] = $value;
        }

        /** @var array{attributes?: array<string, mixed>, partAttributes?: array<string, array<string, mixed>>, templates?: array<string, string>, templateData?: array<string, mixed>, variant?: ?string} $values The merge retains each named bag's declared value type. */
        return $values;
    }
}
