<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Attribute;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Attribute\Form;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests valid and invalid discovery aliases independently of field configuration.
 */
class AliasesTest extends TestCase
{
    public function testBlueprintAliasesAreIndependentOfFieldSettings(): void
    {
        static::assertSame('plain', new Form('plain')->name);
    }

    /**
     * Supply alias mistakes that should fail before a form session is constructed.
     *
     * @return array<string, array{string}>
     */
    public static function invalidFormAliases(): array
    {
        return ['empty' => [''], 'leading whitespace' => [' profile'], 'trailing whitespace' => ['profile ']];
    }

    #[DataProvider('invalidFormAliases')]
    public function testInvalidFormAliasesAreRejected(string $alias): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Form($alias);
    }

    /**
     * Supply control aliases outside the existing lowercase HTML type-name contract.
     *
     * @return array<string, array{string}>
     */
    public static function invalidTypeAliases(): array
    {
        return ['empty' => [''], 'uppercase' => ['DateRange'], 'spaces' => ['date range'], 'markup' => ['<input>']];
    }

    #[DataProvider('invalidTypeAliases')]
    public function testInvalidTypeAliasesAreRejected(string $alias): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AsFieldType($alias);
    }
}
