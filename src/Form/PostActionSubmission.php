<?php
declare(strict_types=1);

namespace FormCompose\Form;

use Cake\Utility\Hash;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Form\Field\NativeValue;
use InvalidArgumentException;

/**
 * Validates action submission methods, form identities, and explicit locked payloads.
 *
 * PostAction validates this configuration before its renderer opens a form. The
 * resulting fields are rendered as locked hidden controls through the session.
 */
final class PostActionSubmission
{
    /**
     * Restrict standalone actions to the supported state-changing request methods.
     */
    public static function method(mixed $method): string
    {
        if (!is_string($method) || !in_array(strtolower($method), ['post', 'put', 'patch', 'delete'], true)) {
            throw new InvalidArgumentException('Post actions support POST, PUT, PATCH, or DELETE.');
        }

        return strtolower($method);
    }

    /**
     * Preserve a usable explicit identity for external buttons and action links.
     */
    public static function id(mixed $id): string
    {
        if (!is_string($id) || $id === '') {
            throw new InvalidArgumentException('Action form IDs must be nonempty strings.');
        }

        return $id;
    }

    /**
     * Flatten nested scalar payloads into validated Cake field paths.
     *
     * @param array<string, mixed> $data Nested action payload.
     * @return array<string, mixed>
     */
    public static function data(array $data): array
    {
        $fields = [];
        /** @var mixed $value */
        foreach (Hash::flatten($data) as $path => $value) {
            $path = (string)$path;
            new FieldDefinition($path);
            if (preg_match('/(?:^|\.)-?[0-9]+$/D', $path)) {
                throw new InvalidArgumentException(
                    'Locked action data must end in a named field, not a numeric index.',
                );
            }

            if (in_array(explode('.', $path)[0], ['_method', '_csrfToken', '_Token'], true)) {
                throw new InvalidArgumentException('Action data cannot replace form infrastructure fields.');
            }

            NativeValue::scalar($value);
            $fields[$path] = $value;
        }

        return $fields;
    }

    /**
     * Keep optional submit names independent of locked payloads and infrastructure.
     *
     * @param array<string, mixed> $data Locked field paths.
     */
    public static function button(array $data, mixed $name): void
    {
        if ($name === null) {
            return;
        }

        $path = str_replace(['[', ']'], ['.', ''], NativeValue::scalar($name));
        new FieldDefinition($path);
        $parts = explode('.', $path);
        if (in_array($parts[0], ['_method', '_csrfToken', '_Token'], true)) {
            throw new InvalidArgumentException('Action buttons cannot submit infrastructure field names.');
        }

        foreach (array_keys($data) as $field) {
            if (array_intersect($parts, explode('.', $field)) !== []) {
                throw new InvalidArgumentException('Action button names cannot overlap locked data paths.');
            }
        }
    }
}
