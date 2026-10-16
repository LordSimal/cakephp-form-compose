<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use FormCompose\FormContext;

/**
 * Extracts collection-owned feedback without flattening errors from its child rows.
 *
 * Repeated layouts and summaries share these messages; CakePHP remains their source.
 */
final class RepeatedErrors
{
    /**
     * Read direct messages only, excluding all nested row error trees.
     *
     * @return list<string>
     */
    public static function messages(FormContext $context, string $path): array
    {
        return array_map(
            static fn(mixed $message): string => (string)$message,
            array_values(array_filter(
                $context->context->error($path),
                static fn(mixed $message): bool => !is_array($message),
            )),
        );
    }
}
