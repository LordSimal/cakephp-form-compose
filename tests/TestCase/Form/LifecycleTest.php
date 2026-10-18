<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use FormCompose\Test\FormTestCase;
use LogicException;

class LifecycleTest extends FormTestCase
{
    public function testFormsHaveIndependentLifecycleAndIds(): void
    {
        $helper = $this->helper();
        $first = $helper->form()->url('/first')->method('patch')->bind();
        $second = $helper->form()->url('/second')->method('get')->bind();
        static::assertStringContainsString('value="PATCH"', $first->open());
        static::assertStringContainsString('method="get"', $second->open());
        static::assertNotSame(
            $first->field('name')->resolve()->state->id,
            $second->field('name')->resolve()->state->id,
        );
        static::assertStringContainsString('form-compose-1-name', $first->control('name')->render());
        static::assertStringContainsString('form-compose-2-name', $second->control('name')->render());
        static::assertStringContainsString('&lt;Save&gt;', $first->submit('<Save>')->render());
        static::assertStringContainsString('</form>', $second->close());
        static::assertStringContainsString('</form>', $first->close());
    }

    public function testInputBeforeOpenIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()->form()->bind()->control('name')->render();
    }

    public function testDuplicateInputIdsAreRejected(): void
    {
        $form = $this->helper()->form()->url('/articles/save')->bind();
        $form->open();
        $form->control('name')->render();
        $this->expectException(LogicException::class);
        $form->control('name')->render();
    }

    public function testReopeningIsRejected(): void
    {
        $form = $this->helper()->form()->url('/articles/save')->bind();
        $form->open();
        $this->expectException(LogicException::class);
        $form->open();
    }

    public function testClosingTwiceIsRejected(): void
    {
        $form = $this->helper()->form()->url('/articles/save')->bind();
        $form->open();
        $form->close();
        $this->expectException(LogicException::class);
        $form->close();
    }

    public function testFileInputRequiresMultipartForm(): void
    {
        $form = $this->helper()->form()->url('/articles/save')->bind();
        $form->open();
        $this->expectException(LogicException::class);
        $form->control('upload')->type('file')->render();
    }

    public function testFileInputDoesNotEchoAValue(): void
    {
        $form = $this->helper()
            ->form()
            ->url('/articles/save')
            ->multipart()
            ->bind();
        static::assertStringContainsString('multipart/form-data', $form->open());
        static::assertStringNotContainsString(
            'value=',
            $form->control('upload')->type('file')->value('private-path')->render(),
        );
        $form->close();
    }
}
