<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
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
        $name = new FieldDefinition('name', ['label' => 'Full name']);
        $contact = $empty->add($name);
        $extended = $contact->add(new FieldDefinition('email', ['type' => 'email']));

        static::assertSame([], $empty->fields);
        static::assertSame([$name], $contact->fields);
        static::assertSame(['name', 'email'], array_column($extended->fields, 'path'));
        static::assertSame($name, $extended->field('name'));
    }

    public function testOverridesPreserveOrderAndUnchangedConfiguration(): void
    {
        $email = new FieldDefinition('email', [
            'type' => 'email',
            'label' => 'Email',
            'help' => 'Work address',
            'attributes' => ['class' => 'original', 'data-original' => true],
        ]);
        $contact = new FormDefinition([new FieldDefinition('name'), $email]);
        $customized = $contact->with('email', [
            'label' => false,
            'value' => null,
            'attributes' => ['class' => 'replacement'],
        ]);

        static::assertSame(['name', 'email'], array_column($customized->fields, 'path'));
        static::assertSame($contact->field('name'), $customized->field('name'));
        static::assertSame('Email', $contact->field('email')->options['label']);
        static::assertSame(['class' => 'original', 'data-original' => true], $email->options['attributes']);
        static::assertSame(
            [
                'type' => 'email',
                'label' => false,
                'help' => 'Work address',
                'attributes' => ['class' => 'replacement'],
                'value' => null,
            ],
            $customized->field('email')->options,
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
        new FormDefinition([])->with('missing', ['label' => 'Typo']);
    }

    public function testAddingDuplicatePathsIsRejected(): void
    {
        $contact = new FormDefinition([])->add(new FieldDefinition('email', ['type' => 'email']));
        $this->expectException(InvalidArgumentException::class);
        $contact->add(new FieldDefinition('email', ['label' => 'Duplicate']));
    }

    public function testConstructorRejectsDuplicatePaths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FormDefinition([new FieldDefinition('name'), new FieldDefinition('name')]);
    }
}
