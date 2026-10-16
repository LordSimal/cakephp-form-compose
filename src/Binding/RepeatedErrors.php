<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Utility\Hash;
use FormCompose\FormContext;

/**
 * Extracts collection-owned feedback without flattening errors from its child rows.
 *
 * Repeated layouts and summaries share these messages; CakePHP remains their source.
 */
final class RepeatedErrors
{
    /**
     * Exclude row error trees, including rows omitted from the rendered selection.
     *
     * @param list<int|string> $keys Explicitly selected keys, including blank rows.
     * @return list<string>
     */
    public static function messages(FormContext $context, string $path, array $keys): array
    {
        /** @var mixed $value */
        $value = $context->value($path);
        if (is_iterable($value)) {
            /** @var iterable<array-key, mixed> $rows */
            $rows = $value;
            $keys = [...$keys, ...array_keys(is_array($rows) ? $rows : iterator_to_array($rows))];
        }

        $errors = $context->context->error($path);
        foreach ($keys as $key) {
            if (!isset($errors[$key]) || !is_array($errors[$key])) {
                continue;
            }

            unset($errors[$key]);
        }

        return array_map(static fn(mixed $message): string => (string)$message, array_values(Hash::flatten($errors)));
    }
}
