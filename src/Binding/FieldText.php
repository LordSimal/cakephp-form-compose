<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Closure;
use FormCompose\Rendering\Html;
use InvalidArgumentException;

/**
 * Resolves deferred label/help options before FieldResolver creates immutable state.
 *
 * Keeps application text callbacks separate from field binding and rendering.
 * Only label and help accept callbacks; other options retain their own contracts.
 *
 * @internal Used after blueprint defaults and per-call options merge.
 */
final class FieldText
{
    /**
     * Evaluate deferred label/help text once for this field's immutable state.
     *
     * Runs after option merging so overridden closures never execute. Other
     * configuration bags remain untouched. Exceptions from application closures
     * propagate before rendering or submission registration can occur.
     *
     * @param array<string, mixed> $options Merged field configuration.
     * @return array<string, mixed>
     * @throws \InvalidArgumentException When a text closure returns an unsupported value.
     */
    public static function resolve(array $options): array
    {
        foreach (['label', 'help'] as $key) {
            if (!($options[$key] ?? null) instanceof Closure) {
                continue;
            }

            /** @var mixed $text */
            $text = $options[$key]();
            if ($text !== null && $text !== false && !is_string($text) && !$text instanceof Html) {
                throw new InvalidArgumentException(
                    'The ' . $key . ' closure must return a string, Html, false, or null.',
                );
            }

            $options[$key] = $text;
        }

        return $options;
    }
}
