<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Test\Fixture\Widget\ConfiguredType;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use ReflectionClass;

/** Rejects application composite declarations that accidentally return bound controls instead of relative specs. */
class CompositeDeclarationFailureTest extends FormTestCase
{
    public function testAControlReturnedAsAChildFailsWithoutRegisteringTheParent(): void
    {
        $helper = $this->helper();
        $foreign = $helper->form()->bind()->control('foreign');
        // Native array parameters accept invalid entries; bypass static shape checking for this failure case.
        $type = new ReflectionClass(ConfiguredType::class)->newInstance([$foreign]);
        static::assertInstanceOf(ConfiguredType::class, $type);
        $form = $helper->form()->types(['invalid-children' => $type])->bind();
        $form->open();
        try {
            $form->control('parent')->type('invalid-children')->render();
            static::fail('A composite must reject already bound controls as relative child declarations.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertSame(
                'Composite children must be relative FieldSpec declarations.',
                $invalidArgumentException->getMessage(),
            );
        }

        static::assertStringContainsString('name="parent"', $form->control('parent')->render());
        $form->close();
    }
}
