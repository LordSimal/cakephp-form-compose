<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use FormCompose\Attribute\Embed;
use FormCompose\Attribute\EmbedMany;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormBlueprint;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Collects inherited static metadata before resolving request-dependent children.
 *
 * Redeclared embeds replace their reference in place, so discarded parent embeds
 * never run hooks. Same-level duplicates fail; inherited field options merge.
 */
final class BlueprintDeclarations
{
    /**
     * Read class attributes in parent-to-child order without constructing embeds.
     *
     * @return array{entries: array<string, \FormCompose\Field\FieldDefinition|\FormCompose\Attribute\Embed|\FormCompose\Attribute\EmbedMany>, options: array<string, mixed>}
     */
    public static function read(FormBlueprint $blueprint): array
    {
        $reflection = new ReflectionClass($blueprint);
        $hierarchy = [$reflection];
        while (($parent = $reflection->getParentClass()) !== false) {
            array_unshift($hierarchy, $parent);
            $reflection = $parent;
        }

        /** @var array<string, \FormCompose\Field\FieldDefinition|\FormCompose\Attribute\Embed|\FormCompose\Attribute\EmbedMany> $entries */
        $entries = [];
        $options = [];
        foreach ($hierarchy as $level) {
            $declared = self::level($level);
            $options = array_replace($options, $declared['options']);
            foreach ($declared['entries'] as $path => $entry) {
                $previous = $entries[$path] ?? null;
                if ($previous instanceof FieldDefinition && $entry instanceof FieldDefinition) {
                    $entry = $previous->with($entry->options);
                } elseif ($previous !== null && $previous::class !== $entry::class) {
                    throw new InvalidArgumentException('Cannot replace a field with an embed or an embed with a field: '
                        . $path);
                }

                $entries[$path] = $entry;
            }
        }

        return ['entries' => $entries, 'options' => $options];
    }

    /**
     * Read one class while enforcing uniqueness independently of inheritance.
     *
     * @param \ReflectionClass<\FormCompose\FormBlueprint>|\ReflectionClass<object> $level One inheritance level.
     * @return array{entries: array<string, \FormCompose\Field\FieldDefinition|\FormCompose\Attribute\Embed|\FormCompose\Attribute\EmbedMany>, options: array<string, mixed>}
     */
    private static function level(ReflectionClass $level): array
    {
        $entries = [];
        $options = [];
        foreach ($level->getAttributes() as $attribute) {
            if (!in_array($attribute->getName(), [Form::class, Field::class, Embed::class, EmbedMany::class], true)) {
                continue;
            }

            /** @var \FormCompose\Attribute\Form|\FormCompose\Attribute\Field|\FormCompose\Attribute\Embed|\FormCompose\Attribute\EmbedMany $declaration */
            $declaration = $attribute->newInstance();
            if ($declaration instanceof Form) {
                $options = $declaration->options();
                continue;
            }

            $entry = $declaration instanceof Field ? $declaration->definition() : $declaration;
            if (isset($entries[$entry->path])) {
                throw new InvalidArgumentException(
                    'Form definitions cannot contain duplicate field paths or embed paths.',
                );
            }

            $entries[$entry->path] = $entry;
        }

        return ['entries' => $entries, 'options' => $options];
    }
}
