<?php
declare(strict_types=1);

namespace FormCompose\Field;

use InvalidArgumentException;

/**
 * Normalizes batch control declarations before a session or layout renders them.
 *
 * Indexed paths can be mixed with path-to-options entries. The
 * complete batch is validated first, preserving order and explicit option values.
 */
final class ControlList
{
    /**
     * Convert the supported batch syntax into ordered field definitions.
     *
     * @param array<array-key, mixed> $fields Indexed paths or paths mapped to options.
     * @return list<\FormCompose\Field\FieldDefinition>
     * @throws \InvalidArgumentException For invalid entries or field paths.
     */
    public static function normalize(array $fields): array
    {
        $definitions = [];
        /** @var mixed $field */
        foreach ($fields as $path => $field) {
            if (is_int($path) && is_string($field)) {
                $definitions[] = new FieldDefinition($field);
            } elseif (is_string($path) && is_array($field)) {
                foreach (array_keys($field) as $option) {
                    if (!is_string($option)) {
                        throw new InvalidArgumentException('Control options must use string keys.');
                    }
                }

                /** @var array<string, mixed> $field Validated option keys. */
                $definitions[] = new FieldDefinition($path, $field);
            } else {
                throw new InvalidArgumentException(
                    'Use indexed field paths or paths mapped to options.',
                );
            }
        }

        return $definitions;
    }
}
