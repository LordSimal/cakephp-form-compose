<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use Cake\View\Form\ContextInterface;

/**
 * Discovers the backed enum class paired with a CakePHP enum schema type.
 *
 * FieldConventions uses this metadata before conventional view-variable lookup.
 * Discovery neither enumerates cases nor evaluates their labels; native rendering
 * prepares the actual choices through the ordinary Choices pipeline.
 */
final class EnumChoices
{
    /**
     * Read enum metadata without guessing from the currently bound value.
     *
     * @return class-string<\BackedEnum>|null
     */
    public static function discover(ContextInterface $context, string $path): ?string
    {
        $type = $context->type($path);
        if ($type === null || !str_starts_with($type, 'enum-')) {
            return null;
        }

        $databaseType = TypeFactory::build($type);

        return $databaseType instanceof EnumType ? $databaseType->getEnumClassName() : null;
    }
}
