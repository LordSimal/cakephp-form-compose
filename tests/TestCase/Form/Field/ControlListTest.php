<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\ControlList;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Form\Field\FieldSpec;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Checks shared batch syntax before either rendering entry point has side effects.
 */
class ControlListTest extends TestCase
{
    public function testMixedDeclarationsPreserveOrderAndExplicitValues(): void
    {
        $email = new FieldSpec('email', new FieldConfig(type: 'email', label: 'Work email'));
        /** @var list<\FormCompose\Form\Field\FieldSpec> $fields */
        $fields = ControlList::normalize([
            'first_name',
            $email,
            new FieldSpec(
                'author.name',
                FieldConfig::builder()
                    ->label(false)
                    ->value(null)
                    ->required(false),
            ),
            new FieldSpec('count', FieldConfig::builder()->value(0)),
            new FieldSpec('empty', FieldConfig::builder()->value('')),
        ]);
        static::assertSame(['first_name', 'email', 'author.name', 'count', 'empty'], array_column($fields, 'path'));
        static::assertSame($email, $fields[1]);
        static::assertFalse($fields[0]->config->hasValue);
        static::assertFalse($fields[2]->config->label);
        static::assertTrue($fields[2]->config->hasValue);
        static::assertNull($fields[2]->config->value);
        static::assertFalse($fields[2]->config->required);
        static::assertSame(0, $fields[3]->config->value);
        static::assertSame('', $fields[4]->config->value);
        static::assertSame([], ControlList::normalize([]));
    }

    /**
     * @param array<array-key, mixed> $fields Invalid declarations.
     */
    #[DataProvider('invalidDeclarations')]
    public function testInvalidDeclarationsFailClearly(array $fields): void
    {
        $this->expectException(InvalidArgumentException::class);
        ControlList::normalize($fields);
    }

    /**
     * @return array<string, array{array<array-key, mixed>}>
     */
    public static function invalidDeclarations(): array
    {
        return [
            'integer' => [[42]],
            'null' => [[null]],
            'boolean' => [[false]],
            'indexed options' => [[['label' => 'Name']]],
            'indexed definition' => [[new FieldDefinition('email')]],
            'named definition' => [['email' => new FieldDefinition('email')]],
            'invalid path' => [['name', 'invalid path' => []]],
            'invalid indexed path' => [['name', '']],
            'numeric option' => [['email' => [0 => 'label']]],
        ];
    }
}
