<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use InvalidArgumentException;

/**
 * Defines native schema defaults and validates application control-type mappings.
 *
 * FormHelper combines helper and binding overrides; FormBinding snapshots them
 * over these defaults. FieldResolver consults the resulting map after explicit
 * types, association/choice conventions, and terminal-name conventions.
 */
final class TypeMap
{
    public const array FIELD_NAMES = ['email' => 'email', 'tel' => 'tel', 'telephone' => 'tel', 'phone' => 'tel'];

    public const array DEFAULTS = [
        'string' => 'text',
        'text' => 'textarea',
        'boolean' => 'checkbox',
        'integer' => 'number',
        'tinyinteger' => 'number',
        'smallinteger' => 'number',
        'biginteger' => 'number',
        'decimal' => 'number',
        'float' => 'number',
        'datetime' => 'datetime-local',
        'timestamp' => 'datetime-local',
        'datetimefractional' => 'datetime-local',
        'timestampfractional' => 'datetime-local',
        'timestamptimezone' => 'datetime-local',
        'date' => 'date',
        'time' => 'time',
        'binary' => 'file',
    ];

    /**
     * Validate override entries without replacing unrelated schema mappings.
     *
     * @return array<string, string> Schema type to control alias overrides.
     * @throws \InvalidArgumentException For malformed maps or invalid control aliases.
     */
    public static function normalize(mixed $map): array
    {
        if (!is_array($map)) {
            throw new InvalidArgumentException('The typeMap option must be an array of schema types to control types.');
        }

        $types = [];
        /** @var mixed $type */
        foreach ($map as $schema => $type) {
            if (
                !is_string($schema)
                || $schema === ''
                || !is_string($type)
                || !preg_match('/^[a-z][a-z0-9_-]*$/D', $type)
            ) {
                throw new InvalidArgumentException(
                    'Each typeMap entry must map a nonempty schema type to a valid control type.',
                );
            }

            $types[$schema] = $type;
        }

        return $types;
    }
}
