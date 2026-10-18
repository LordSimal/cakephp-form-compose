<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

/**
 * Verifies explicit control layouts through inline options and reusable definitions.
 */
class TemplateOverrideTest extends FormTestCase
{
    public function testShorthandOverridesThemeLookupWhilePreservingPartTemplates(): void
    {
        $form = $this->helper(['email' => 'posted@example.test'])
            ->form(new ArrayContext(['errors' => ['email' => ['invalid' => '<Invalid address>']]]))
            ->theme(new Theme(['themes/application']))
            ->bind();
        $form->open();

        $html = $form->control('email')
            ->type('email')
            ->variant('compact')
            ->partTemplate('control', 'themes/custom/control')
            ->partTemplate('input', 'themes/custom/input')
            ->help('<Work address>')
            ->attributes(['x-model' => 'email'])
            ->render();
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
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = $form->control('email')->type('email')->partTemplate('control', 'themes/custom/control')->render();
        $form->close();

        static::assertStringContainsString('data-control="custom"', $html);
        static::assertStringContainsString('type="email"', $html);
    }

    public function testMatchingControlOverridesAreAcceptedAndNormalized(): void
    {
        $form = $this->helper()->form()->bind();
        $field = $form->field('email')
            ->template('themes/custom/control')
            ->partTemplate('control', 'themes/custom/control')
            ->resolve();
        static::assertSame(['control' => 'themes/custom/control'], $field->state->config->presentation->templates);
        $form->open();
        static::assertStringContainsString('data-control="custom"', $field->control());
        $form->close();
    }

    public function testLaterControlSelectionsReplaceEarlierSelections(): void
    {
        $field = $this->helper()
            ->form()
            ->bind()
            ->field('email')
            ->template('themes/custom/control')
            ->partTemplate('control', 'themes/application/control')
            ->resolve();
        static::assertSame(['control' => 'themes/application/control'], $field->state->config->presentation->templates);
    }

    public function testDefinitionsKeepTheirShorthandWhileBoundFieldsUseCanonicalOptions(): void
    {
        $contact = new FormDefinition([])->add(
            new FieldDefinition(
                'email',
                FieldConfig::builder()->type('email')->partTemplate('control', 'themes/custom/control')->config(),
            ),
        );
        $form = $this->helper()->form()->bind();
        $selected = $form->field($contact->field('email')->path)
            ->apply(
                $contact->field('email')->config->merge(
                        FieldConfig::builder()->partTemplate('control', 'themes/library/control')->config(),
                    ),
            )
            ->resolve();
        static::assertSame(['control' => 'themes/library/control'], $selected->state->config->presentation->templates);
        static::assertSame(
            'themes/custom/control',
            $contact->field('email')->config->presentation->templates['control'],
        );
        $form->open();
        static::assertStringContainsString('data-control="custom"', $form->renderDefinition($contact));
        $form->close();
    }

    public function testParentResumesThemeLookupWithoutApplyingShorthandToChildParts(): void
    {
        $form = $this->helper()
            ->form()
            ->theme(new Theme(['themes/application']))
            ->bind();
        $form->open();

        $html = $form->control('email')->type('email')->partTemplate('control', 'themes/library/control')->render();
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
        $this->expectException(is_string($template) ? InvalidArgumentException::class : TypeError::class);
        if (is_string($template)) {
            $this->expectExceptionMessage('Template parts and element names must be nonempty strings.');
        }

        $this->invokeBuilder(FieldConfig::builder(), 'partTemplate', 'control', $template);
    }
}
