<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Attribute;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use PHPUnit\Framework\TestCase;

/**
 * Checks explicit attribute presentation arguments and positional compatibility.
 */
class BlueprintPresentationTest extends TestCase
{
    public function testFieldPresentationShorthandOverridesOptionsAndPreservesExistingArguments(): void
    {
        $field = new Field(
            'email',
            'email',
            'Email',
            'Help',
            ['template' => 'old', 'templates' => ['input' => 'old']],
            template: 'new',
            templates: ['input' => 'new-input'],
            templateData: [],
        )->definition();
        static::assertSame('email', $field->options['type']);
        static::assertSame('new', $field->options['template']);
        static::assertSame(['input' => 'new-input'], $field->options['templates']);
        static::assertSame([], $field->options['templateData']);
        static::assertSame([], new Form('plain')->options());
        static::assertSame(
            ['templates' => [], 'templateData' => []],
            new Form('explicit', templates: [], templateData: [])->options(),
        );
    }
}
