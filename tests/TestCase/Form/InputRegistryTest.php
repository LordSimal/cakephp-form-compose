<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Form\FormProtector;
use FormCompose\Configuration\FormConfig;
use FormCompose\Form\Field\Submission;
use FormCompose\Form\FormConstruction;
use FormCompose\Form\InputRegistry;
use FormCompose\Rendering\Theme;
use FormCompose\Security\FormProtection;
use FormCompose\Test\FormTestCase;
use LogicException;

/** Verifies standalone registry commits and duplicate identities with CakePHP's actual protection tokens. */
class InputRegistryTest extends FormTestCase
{
    public function testRegistrationOutsideARenderTransactionImmediatelyProtectsTheSubmittedValue(): void
    {
        $binding = $this->helper(attributes: ['formTokenData' => []])->form()->url('/records/save')->bind()->binding;
        $state = new FormConstruction()->prepare($binding, Theme::base(), new FormConfig());
        $protection = new FormProtection($binding->request);
        $protection->start($state);

        $registry = new InputRegistry($protection);
        $registry->register('record-id', [new Submission('id', value: '42')]);

        $tokens = [];
        foreach ($protection->fields($state) as $attributes) {
            $name = (string)$attributes->values['name'];
            $tokens[substr($name, 7, -1)] = (string)$attributes->values['value'];
        }

        $session = $binding->request->getSession()->id();
        static::assertTrue(new FormProtector()->validate(
            ['id' => '42', '_Token' => $tokens],
            '/records/save',
            $session,
        ));
        static::assertFalse(new FormProtector()->validate(
            ['id' => '43', '_Token' => $tokens],
            '/records/save',
            $session,
        ));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already been rendered');
        $registry->register('record-id', [new Submission('id', value: '43')]);
    }
}
