<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Closure;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\Translated;
use FormCompose\Rendering\Html;
use InvalidArgumentException;

/**
 * Resolves deferred label/help options before FieldResolver creates immutable state.
 *
 * Keeps application text callbacks separate from field binding and rendering.
 * Only label and help resolve Translated values or callbacks; other options
 * retain their own contracts.
 *
 * @internal Used after blueprint defaults and per-call options merge.
 */
final class FieldText
{
    /**
     * Evaluate deferred label/help text once for this field's immutable state.
     *
     * Runs after option merging so overridden messages and closures never execute. Other
     * configuration bags remain untouched. Exceptions from application closures
     * propagate before rendering or submission registration can occur.
     *
     * @throws \InvalidArgumentException When a text closure returns an unsupported value.
     */
    public static function resolve(FieldConfig $config): FieldConfig
    {
        foreach (['label', 'help'] as $key) {
            $text = $key === 'label' ? $config->label : $config->help;
            if ($text instanceof Closure) {
                /** @var mixed $text */
                $text = $text();
                if (
                    $text !== null
                    && $text !== false
                    && !is_string($text)
                    && !$text instanceof Html
                    && !$text instanceof Translated
                ) {
                    throw new InvalidArgumentException(
                        'The ' . $key . ' closure must return a string, Html, Translated, false, or null.',
                    );
                }
            }

            if ($text instanceof Translated) {
                $text = $text->translate();
            }

            $config = $key === 'label' ? $config->withLabel($text) : $config->withHelp($text);
        }

        return $config;
    }
}
