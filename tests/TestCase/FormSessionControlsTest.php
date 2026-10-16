<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\Form\FormProtector;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Form\FormPhase;
use FormCompose\FormSession;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\Theme;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\EmailForm;
use FormCompose\Test\Fixture\ContactFormHelper;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Exercises ordered batch rendering, extension dispatch, and atomic input registration.
 */
class FormSessionControlsTest extends CompositionTestCase
{
    public function testMixedBatchControlsPreserveDefaultsOverridesAndExtensions(): void
    {
        $helper = new ContactFormHelper($this->helper()->getView());
        $form = $helper->bind(definition: EmailForm::class);
        $form->open();

        $html = $form->controls([
            'website',
            'email' => ['label' => 'Work <email>'],
            'author.name' => ['value' => 'Ada', 'label' => false],
        ]);
        static::assertStringContainsString('type="url"', $html);
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('Work &lt;email&gt;', $html);
        static::assertStringContainsString('name="author[name]"', $html);
        static::assertLessThan(strpos($html, 'name="email"'), strpos($html, 'name="website"'));
        static::assertSame('Default email', $form->field('email')->state->options['label']);
        static::assertSame('', $form->controls([]));
        $form->close();
    }

    public function testBatchControlsCallOverriddenControlMethods(): void
    {
        $base = $this->helper()->bind();
        $form = new class($base->binding, new RenderContext(
            new PhpRenderer($this->helper()->getView(), Theme::base()),
            Theme::base(),
        )) extends FormSession {
            /**
             * Supply an application control override used by batch rendering.
             *
             * @param array<string, mixed> $options Field options.
             */
            public function control(string $field, array $options = []): string
            {
                return '<section data-custom-control>' . parent::control($field, $options) . '</section>';
            }
        };
        $form->open();
        static::assertSame(2, substr_count($form->controls(['name', 'email']), 'data-custom-control'));
        $form->close();
    }

    public function testTheEntireBatchIsValidatedBeforeAnyControlIsRendered(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->controls(['name', 'email' => false]);
            static::fail('Expected invalid batch syntax to fail.');
        } catch (InvalidArgumentException) {
            static::assertStringContainsString('name="name"', $form->control('name'));
        }

        $form->close();
    }

    public function testFailedBatchesRollBackProtectionAndIdsButPreserveExistingInputs(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['url' => '/articles/save']);
        $html = $form->open() . $form->control('existing');
        try {
            $form->controls(['name', 'attachment' => ['type' => 'file']]);
            static::fail('Expected multipart enforcement to fail.');
        } catch (LogicException $logicException) {
            static::assertSame('File inputs require a multipart form.', $logicException->getMessage());
        }

        $html .= $form->controls(['name', 'email']) . $form->close();
        $data = [
            'existing' => 'Kept',
            'name' => 'Ada',
            'email' => 'ada@example.test',
            '_Token' => $this->tokens($html),
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            $data + ['attachment' => 'extra'],
            '/articles/save',
            $session,
        ));
    }

    public function testDuplicateInputsRollBackTheBatch(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->controls(['name', 'name']);
            static::fail('Expected duplicate IDs to fail.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        static::assertStringContainsString('name="name"', $form->control('name'));
        $form->close();
    }

    #[DataProvider('nonOpenPhases')]
    public function testNonemptyBatchesRequireAnOpenSession(FormPhase $phase): void
    {
        $form = $this->helper()->bind();
        if ($phase === FormPhase::Closed) {
            $form->render();
        }

        $this->expectException(LogicException::class);
        $form->controls(['name']);
    }

    /**
     * @return array<string, array{\FormCompose\Form\FormPhase}>
     */
    public static function nonOpenPhases(): array
    {
        return ['pending' => [FormPhase::Pending], 'closed' => [FormPhase::Closed]];
    }

    /**
     * Extract real Cake tokens for validating successful and tampered submissions.
     *
     * @return array<string, string>
     */
    protected function tokens(string $html): array
    {
        /** @var NodeList<Element>|false $nodes */
        $nodes = $this->xpath($html)->query('//input[starts-with(@name, "_Token[")]');
        static::assertInstanceOf(NodeList::class, $nodes);
        $tokens = [];
        foreach ($nodes as $node) {
            $name = $node->getAttribute('name');
            $value = $node->getAttribute('value');
            static::assertNotNull($name);
            static::assertNotNull($value);
            $tokens[substr($name, 7, -1)] = $value;
        }

        return $tokens;
    }
}
