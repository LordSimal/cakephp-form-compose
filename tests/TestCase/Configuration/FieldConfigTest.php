<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Configuration;

use FormCompose\Configuration\FieldConfig;
use FormCompose\Rendering\Html;
use PHPUnit\Framework\TestCase;

/** Checks named configuration and canonical template selection independently of discovery metadata. */
class FieldConfigTest extends TestCase
{
    public function testConfigurationPreservesNamedSettingsAndLastTemplateSelection(): void
    {
        $builder = FieldConfig::builder(new FieldConfig(type: 'email', label: 'Email', help: 'Help'))
            ->template('old')
            ->partTemplate('input', 'old-input');
        $previous = $builder->config();
        $field = $builder->template('new')->partTemplate('input', 'new-input')->templateData([])->config();
        static::assertSame('email', $field->type);
        static::assertSame('Email', $field->label);
        static::assertSame('Help', $field->help);
        static::assertSame(['control' => 'new', 'input' => 'new-input'], $field->presentation->templates);
        static::assertSame('old', $previous->presentation->templates['control']);
        static::assertSame([], $field->presentation->templateData);
    }

    public function testNamedConfigurationAndFluentOverridesPreserveExplicitValues(): void
    {
        $help = new Html('<strong>Trusted help</strong>');
        $original = new FieldConfig(type: 'text', label: 'Original', help: 'Original');
        $field = FieldConfig::builder($original)
            ->type('email')
            ->label(false)
            ->help($help)
            ->value(null)
            ->disabled(false)
            ->extensionData(['data-empty' => '', 'data-zero' => 0])
            ->config();
        static::assertSame('email', $field->type);
        static::assertFalse($field->label);
        static::assertSame($help, $field->help);
        static::assertTrue($field->hasValue);
        static::assertNull($field->value);
        static::assertFalse($field->disabled);
        static::assertSame('', $field->extensionData['data-empty']);
        static::assertSame(0, $field->extensionData['data-zero']);
        static::assertSame('Original', $original->label);
        static::assertFalse($original->hasValue);
    }
}
