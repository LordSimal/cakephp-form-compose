<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use FormCompose\Field\ControlList;
use FormCompose\Field\FieldDefinition;
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
        $email = FieldDefinition::make('email', 'email')->label('Work email');
        $fields = ControlList::normalize([
            'first_name',
            $email,
            'author.name' => ['label' => false, 'value' => null, 'required' => false],
            'count' => ['value' => 0],
            'empty' => ['value' => ''],
        ]);
        static::assertSame(['first_name', 'email', 'author.name', 'count', 'empty'], array_column($fields, 'path'));
        static::assertSame($email, $fields[1]);
        static::assertSame([], $fields[0]->options);
        static::assertSame(['label' => false, 'value' => null, 'required' => false], $fields[2]->options);
        static::assertSame(['value' => 0], $fields[3]->options);
        static::assertSame(['value' => ''], $fields[4]->options);
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
            'named string' => [['email' => 'email']],
            'named definition' => [['email' => FieldDefinition::make('email')]],
            'invalid path' => [['name', 'invalid path' => []]],
            'invalid indexed path' => [['name', '']],
            'numeric option' => [['email' => [0 => 'label']]],
        ];
    }
}
