<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\View\Form\ArrayContext;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies explicit control layouts through inline options and reusable definitions.
 */
class TemplateOverrideTest extends FormTestCase
{
    public function testShorthandOverridesThemeLookupWhilePreservingPartTemplates(): void
    {
        $form = $this->helper(['email' => 'posted@example.test'])->bind(
            new ArrayContext(['errors' => ['email' => ['invalid' => '<Invalid address>']]]),
            theme: new Theme(['themes/application']),
        );
        $form->open();

        $html = $form->control('email', [
            'type' => 'email',
            'variant' => 'compact',
            'template' => 'themes/custom/control',
            'templates' => ['input' => 'themes/custom/input'],
            'help' => '<Work address>',
            'attributes' => ['x-model' => 'email'],
        ]);
        $form->close();

        static::assertStringContainsString('data-control="custom"', $html);
        static::assertStringContainsString('data-field="custom"', $html);
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('value="posted@example.test"', $html);
        static::assertStringContainsString('x-model="email"', $html);
        static::assertStringContainsString('&lt;Work address&gt;', $html);
        static::assertStringContainsString('&lt;Invalid address&gt;', $html);
        static::assertStringContainsString(
            'aria-describedby="form-compose-1-email-help form-compose-1-email-errors"',
            $html,
        );
        static::assertStringNotContainsString('data-variant=', $html);
        static::assertStringNotContainsString('data-layout=', $html);
        static::assertStringNotContainsString('template=', $html);
    }

    public function testPluralControlOverrideStillWorks(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('email', [
            'type' => 'email',
            'templates' => ['control' => 'themes/custom/control'],
        ]);
        $form->close();

        static::assertStringContainsString('data-control="custom"', $html);
        static::assertStringContainsString('type="email"', $html);
    }

    public function testMatchingControlOverridesAreAcceptedAndNormalized(): void
    {
        $form = $this->helper()->bind();
        $field = $form->field('email', [
            'template' => 'themes/custom/control',
            'templates' => ['control' => 'themes/custom/control'],
        ]);
        static::assertSame(['templates' => ['control' => 'themes/custom/control']], $field->state->options);
        $form->open();
        static::assertStringContainsString('data-control="custom"', $field->control());
        $form->close();
    }

    public function testConflictingControlOverridesAreRejectedBeforeRendering(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The template option conflicts with templates[control].');
        $this->helper()
            ->bind()
            ->field('email', [
                'template' => 'themes/custom/control',
                'templates' => ['control' => 'themes/application/control'],
            ]);
    }

    public function testDefinitionsKeepTheirShorthandWhileBoundFieldsUseCanonicalOptions(): void
    {
        $contact = FormDefinition::make()->add(
            FieldDefinition::make('email', 'email')->with(['template' => 'themes/custom/control']),
        );
        $form = $this->helper()->bind();
        $selected = $form->field($contact->field('email'), ['template' => 'themes/library/control']);
        static::assertSame(['control' => 'themes/library/control'], $selected->state->options['templates']);
        static::assertArrayNotHasKey('template', $selected->state->options);
        static::assertSame('themes/custom/control', $contact->field('email')->options['template']);
        $form->open();
        static::assertStringContainsString('data-control="custom"', $form->definition($contact));
        $form->close();
    }

    public function testParentResumesThemeLookupWithoutApplyingShorthandToChildParts(): void
    {
        $form = $this->helper()->bind(theme: new Theme(['themes/application']));
        $form->open();

        $html = $form->control('email', [
            'type' => 'email',
            'template' => 'themes/library/control',
        ]);
        $form->close();

        static::assertSame(1, substr_count($html, 'data-layout="library"'));
        static::assertSame(1, substr_count($html, 'data-layout="application"'));
        static::assertSame(1, substr_count($html, 'type="email"'));
    }

    /**
     * Values that cannot name an explicit control element.
     *
     * @return array<string, array{mixed}>
     */
    public static function invalidTemplates(): array
    {
        return [
            'empty' => [''],
            'null' => [null],
            'boolean' => [false],
            'number' => [42],
            'array' => [['forms/email']],
        ];
    }

    #[DataProvider('invalidTemplates')]
    public function testInvalidShorthandIsRejected(mixed $template): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The template option must be a nonempty element name.');
        $this->helper()->bind()->field('email', ['template' => $template]);
    }
}
