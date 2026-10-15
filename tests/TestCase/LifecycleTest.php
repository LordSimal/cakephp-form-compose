<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use FormCompose\Test\FormTestCase;
use LogicException;

class LifecycleTest extends FormTestCase
{
    public function testFormsHaveIndependentLifecycleAndIds(): void
    {
        $helper = $this->helper();
        $first = $helper->bind(null, ['url' => '/first', 'method' => 'patch']);
        $second = $helper->bind(null, ['url' => '/second', 'method' => 'get']);
        static::assertStringContainsString('value="PATCH"', $first->open());
        static::assertStringContainsString('method="get"', $second->open());
        static::assertNotSame($first->field('name')->state->id, $second->field('name')->state->id);
        static::assertStringContainsString('form-compose-1-name', $first->control('name'));
        static::assertStringContainsString('form-compose-2-name', $second->control('name'));
        static::assertStringContainsString('&lt;Save&gt;', $first->submit('<Save>'));
        static::assertStringContainsString('</form>', $second->close());
        static::assertStringContainsString('</form>', $first->close());
    }

    public function testInputBeforeOpenIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()->bind()->control('name');
    }

    public function testDuplicateInputIdsAreRejected(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save']);
        $form->control('name');
        $this->expectException(LogicException::class);
        $form->control('name');
    }

    public function testReopeningIsRejected(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save']);
        $this->expectException(LogicException::class);
        $form->open();
    }

    public function testClosingTwiceIsRejected(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save']);
        $form->close();
        $this->expectException(LogicException::class);
        $form->close();
    }

    public function testFileInputRequiresMultipartForm(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save']);
        $this->expectException(LogicException::class);
        $form->control('upload', ['type' => 'file']);
    }

    public function testFileInputDoesNotEchoAValue(): void
    {
        $form = $this->helper()->bind(null, ['url' => '/articles/save', 'type' => 'file']);
        static::assertStringContainsString('multipart/form-data', $form->open());
        static::assertStringNotContainsString('value=', $form->control('upload', [
            'type' => 'file',
            'value' => 'private-path',
        ]));
        $form->close();
    }

    public function testRenderingWithoutDefinitionIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()->bind()->render();
    }
}
