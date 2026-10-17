<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use FormCompose\Test\Fixture\Widget\ConfiguredType;
use FormCompose\Test\Fixture\Widget\DateRangeType;
use FormCompose\Test\FormTestCase;
use LogicException;
use RuntimeException;

/**
 * Validates actual composite payloads with Cake's real FormProtector.
 */
class CompositeSecurityTest extends FormTestCase
{
    public function testInspectedAndOmittedChildrenAreNotRegisteredAndHiddenValuesStayLocked(): void
    {
        $csrf = bin2hex(random_bytes(16));
        $helper = $this->helper(attributes: ['formTokenData' => [], 'csrfToken' => $csrf]);
        $form = $helper->bind(null, ['types' => ['range' => new ConfiguredType([
            'start' => ['type' => 'hidden', 'value' => 42],
            'end' => [],
        ])]]);
        $field = $form->field('period', ['type' => 'range', 'templates' => ['composite' => 'widgets/start-only']]);
        static::assertSame('period[end]', $field->inputs['end']->state->name);
        $html = $form->open(['method' => 'patch']) . $field->control() . $form->close();
        static::assertStringContainsString('name="_csrfToken" value="' . $csrf . '"', $html);
        static::assertStringContainsString('name="_method" value="PATCH"', $html);
        $payload = ['period' => ['start' => '42'], '_Token' => $this->tokens($html)];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($payload, '/articles/save', $session));
        $payload['period']['end'] = 'unexpected';
        static::assertFalse(new FormProtector()->validate($payload, '/articles/save', $session));
        unset($payload['period']['end']);
        $payload['period']['start'] = '99';
        static::assertFalse(new FormProtector()->validate($payload, '/articles/save', $session));
    }

    public function testDisabledCompositeChildrenAreAbsentFromTheProtectedPayload(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['types' => ['range' => new DateRangeType()]]);
        $field = $form->field('period', ['type' => 'range']);
        $html = $form->open() . $field->input(['disabled' => true]) . $form->control('visible') . $form->close();
        $payload = ['visible' => 'OK', '_Token' => $this->tokens($html)];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($payload, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            $payload + ['period' => ['start' => 'injected']],
            '/articles/save',
            $session,
        ));
    }

    public function testFailedLayoutsRollBackChildTargetsFeedbackIdsAndSubmissions(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $context = new ArrayContext(['errors' => ['period' => ['start' => ['invalid' => 'Fix start']]]]);
        $form = $helper->bind($context, ['types' => ['range' => new DateRangeType()]]);
        $field = $form->field('period', ['type' => 'range', 'templates' => ['composite' => 'widgets/failure']]);
        $html = $form->open();
        try {
            $field->control();
            static::fail('Expected composite template failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Composite layout failed', $runtimeException->getMessage());
        }

        static::assertNull($field->focusTarget);
        static::assertNull($field->inputs['start']->focusTarget);
        static::assertSame('', $form->errorSummary());
        $html .= $field->inputs['start']->control() . $form->close();
        $payload = ['period' => ['start' => '2026-10-17'], '_Token' => $this->tokens($html)];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($payload, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            $payload + ['extra' => 'injected'],
            '/articles/save',
            $session,
        ));
    }

    public function testCompositeChildrenShareIdentityCollisionChecksWithOrdinaryFields(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['range' => new DateRangeType()]]);
        $field = $form->field('period', ['type' => 'range']);
        $form->open();
        $field->inputs['start']->input();
        $this->expectException(LogicException::class);
        $form->field('period.start')->input();
    }

    /**
     * Read actual Cake tokens from rendered HTML.
     *
     * @return array<string, string>
     */
    protected function tokens(string $html): array
    {
        $tokens = [];
        foreach (['fields', 'unlocked', 'debug'] as $key) {
            $value = $this->xpath($html)->evaluate('string(//input[@name="_Token[' . $key . ']"]/@value)');
            static::assertIsString($value);
            $tokens[$key] = $value;
        }

        return $tokens;
    }
}
