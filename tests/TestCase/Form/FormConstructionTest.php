<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Core\Configure;
use Cake\View\Form\ArrayContext;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

class FormConstructionTest extends FormTestCase
{
    public function testGetFormOmitsCsrfAndProtectionInfrastructure(): void
    {
        $form = $this->helper(attributes: [
            'formTokenData' => [],
            'csrfToken' => bin2hex(random_bytes(16)),
        ])->bind(null, ['method' => 'get']);
        $html = $form->open() . $form->control('query') . $form->close();
        static::assertStringContainsString('method="get"', $html);
        static::assertStringNotContainsString('_csrfToken', $html);
        static::assertStringNotContainsString('_Token', $html);
    }

    public function testActionCanUseCakeRoutesOrTheCurrentRequest(): void
    {
        $helper = $this->helper();
        $routed = $helper->bind(null, ['url' => ['controller' => 'Articles', 'action' => 'edit', 4]]);
        static::assertStringContainsString('action="/Articles/edit/4"', $routed->open());
        $routed->close();
        $current = $helper->bind(null, ['url' => false]);
        static::assertStringNotContainsString(' action=', $current->open());
        $current->close();
        $default = $helper->bind();
        static::assertStringContainsString('action="/articles/save"', $default->open());
        $default->close();
    }

    public function testExistingContextDefaultsToPutAndMultipartCanBeExplicit(): void
    {
        $context = new ArrayContext([
            'schema' => [
                'id' => ['type' => 'integer'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
            'defaults' => ['id' => 4],
        ]);
        $form = $this->helper()->bind($context, ['type' => 'file']);
        $open = $form->open();
        static::assertStringContainsString('multipart/form-data', $open);
        static::assertStringContainsString('value="PUT"', $open);
        $form->close();
    }

    public function testUnsupportedMethodIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->bind(null, ['method' => 'invalid'])->open();
    }

    public function testProductionModeDoesNotExposeDebugSecurityData(): void
    {
        Configure::write('debug', false);
        try {
            $form = $this->helper(attributes: ['formTokenData' => []])->bind();
            $html = $form->open() . $form->control('name') . $form->close();
            static::assertStringContainsString('_Token[fields]', $html);
            static::assertStringNotContainsString('_Token[debug]', $html);
        } finally {
            Configure::write('debug', true);
        }
    }
}
