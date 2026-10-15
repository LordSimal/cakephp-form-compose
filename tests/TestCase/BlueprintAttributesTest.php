<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Rendering\Html;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests explicit option precedence, trusted markup and invalid discovery aliases.
 */
class BlueprintAttributesTest extends TestCase
{
    public function testShorthandOverridesOptionsAndPreservesExplicitValues(): void
    {
        $help = new Html('<strong>Trusted help</strong>');
        $field = new Field('email', type: 'email', label: false, help: $help, options: [
            'type' => 'text',
            'label' => 'Overridden',
            'help' => 'Overridden',
            'value' => null,
            'disabled' => false,
            'data-empty' => '',
            'data-zero' => 0,
        ])->definition();
        static::assertSame('email', $field->options['type']);
        static::assertFalse($field->options['label']);
        static::assertSame($help, $field->options['help']);
        static::assertNull($field->options['value']);
        static::assertFalse($field->options['disabled']);
        static::assertSame('', $field->options['data-empty']);
        static::assertSame(0, $field->options['data-zero']);
        static::assertSame(
            ['type' => null, 'label' => null, 'help' => null],
            new Field('email', options: ['type' => null, 'label' => null, 'help' => null])->definition()->options,
        );
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
