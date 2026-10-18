<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use FormCompose\Blueprint\FormDefinition;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Checks reusable collection construction and independent per-use customization.
 */
class FormDefinitionTest extends TestCase
{
    public function testAddingFieldsPreservesOrderAndEarlierCollections(): void
    {
        $empty = new FormDefinition([]);
        $name = new FieldDefinition('name', FieldConfig::builder()->label('Full name'));
        $contact = $empty->add($name);
        $extended = $contact->add(new FieldDefinition('email', FieldConfig::builder()->type('email')));

        static::assertSame([], $empty->fields);
        static::assertSame([$name], $contact->fields);
        static::assertSame(['name', 'email'], array_column($extended->fields, 'path'));
        static::assertSame($name, $extended->field('name'));
    }

    public function testOverridesPreserveOrderAndUnchangedConfiguration(): void
    {
        $email = new FieldDefinition(
            'email',
            FieldConfig::builder()
                ->type('email')
                ->label('Email')
                ->help('Work address')
                ->attributes(['class' => 'original', 'data-original' => true]),
        );
        $contact = new FormDefinition([new FieldDefinition('name'), $email]);
        $customized = $contact->with(
            'email',
            FieldConfig::builder()
                ->label(false)
                ->value(null)
                ->attributes(['class' => 'replacement']),
        );
        $config = $customized->field('email')->config;
        static::assertSame(['name', 'email'], array_column($customized->fields, 'path'));
        static::assertSame($contact->field('name'), $customized->field('name'));
        static::assertSame('Email', $email->config->label);
        static::assertSame(['class' => 'original', 'data-original' => true], $email->config->presentation->attributes);
        static::assertSame('email', $config->type);
        static::assertFalse($config->label);
        static::assertSame('Work address', $config->help);
        static::assertTrue($config->hasValue);
        static::assertNull($config->value);
        static::assertSame(
            ['class' => 'original replacement', 'data-original' => true],
            $config->presentation->attributes,
        );
    }

    public function testLookingUpMissingFieldsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "missing" is not in this definition.');
        new FormDefinition([])->add(new FieldDefinition('name'))->field('missing');
    }

    public function testOverridingMissingFieldsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FormDefinition([])->with('missing', FieldConfig::builder()->label('Typo'));
    }

    public function testAddingDuplicatePathsIsRejected(): void
    {
        $contact = new FormDefinition([])->add(
            new FieldDefinition('email', FieldConfig::builder()->type('email')),
        );
        $this->expectException(InvalidArgumentException::class);
        $contact->add(new FieldDefinition('email', FieldConfig::builder()->label('Duplicate')));
    }

    public function testConstructorRejectsDuplicatePaths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FormDefinition([new FieldDefinition('name'), new FieldDefinition('name')]);
    }
}
