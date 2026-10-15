<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Utility\Inflector;
use function Cake\I18n\__;

/**
 * Interprets CakePHP field paths for association presentation conventions.
 *
 * The submitted path stays unchanged. These utilities derive the association
 * property used for errors, a human label, and the conventional view variable
 * containing choice options, including nested paths such as comments.0.tags._ids.
 */
final class FieldPath
{
    /**
     * Return the collection property for an _ids selector, or null for other fields.
     */
    public static function association(string $path): ?string
    {
        return str_ends_with($path, '._ids') ? substr($path, 0, -5) : null;
    }

    /**
     * Humanize the final property, omitting association ID suffixes.
     */
    public static function label(string $path): string
    {
        $name = basename(str_replace('.', '/', self::association($path) ?? $path));
        $name = str_ends_with($name, '_id') ? substr($name, 0, -3) : $name;

        return __(Inflector::humanize(Inflector::underscore($name)));
    }

    /**
     * Derive tags for tags._ids, authors for author_id, or statuses for status.
     */
    public static function optionsVariable(string $path): string
    {
        $association = self::association($path);
        $name = basename(str_replace('.', '/', $association ?? $path));
        if ($association === null) {
            $name = str_ends_with($name, '_id') ? substr($name, 0, -3) : $name;
            $name = Inflector::pluralize($name);
        }

        return Inflector::variable($name);
    }
}
