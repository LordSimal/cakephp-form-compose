<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\Configuration\EmbedConfig;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Authoring\BodyForm;
use FormCompose\Test\Fixture\Repeat\CommentForm;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use LogicException;

/**
 * Verifies wrapper-free repeated layouts and explicit wrapper attribute handling.
 */
class RepeatedLayoutTest extends FormTestCase
{
    public function testRepeatWrapperIdentitiesParticipateInDuplicateChecks(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $form->embedMany(path: 'comments', blueprint: CommentForm::class)
            ->keys([])
            ->partTemplate('control', 'repeat/comments')
            ->attributes(['id' => 'comments-wrapper'])
            ->render();
        $this->expectException(LogicException::class);
        $form->control('other')->id('comments-wrapper')->render();
    }

    public function testDefaultLayoutRendersRowsAndErrorsWithoutAWrapper(): void
    {
        $context = new ArrayContext([
            'defaults' => ['comments' => [2 => ['body' => 'First'], 'new' => ['body' => 'Second']]],
            'errors' => ['comments' => ['count' => 'Add another comment']],
        ]);
        $form = $this->helper()->form($context)->bind();
        $form->open();

        $html = $form->embedMany(path: 'comments', blueprint: BodyForm::class)->render();
        $xpath = $this->xpath($html);
        static::assertSame(2, $xpath->query('/html/body/div/input')->length);
        static::assertSame(1, $xpath->query('/html/body/ul/li')->length);
        static::assertSame(0, $xpath->query('/html/body/div/div')->length);
        static::assertStringContainsString('name="comments[2][body]"', $html);
        static::assertStringContainsString('name="comments[new][body]"', $html);
        static::assertLessThan(strpos($html, 'Second'), strpos($html, 'First'));
        static::assertStringContainsString('Add another comment', $html);
        $form->close();
    }

    public function testWrapperAttributesRequireACustomTemplateAndCanBeRetried(): void
    {
        $form = $this->helper(['comments' => [2 => ['body' => 'Comment']]])
            ->form()
            ->bind();
        $form->open();

        $options = EmbedConfig::builder()
            ->attributes(['id' => 'comments-wrapper', 'data-heading' => '<Comments>'])
            ->label('Discussion <comments>');
        try {
            $form->embedMany(path: 'comments', blueprint: CommentForm::class)->apply($options)->render();
            static::fail('Expected wrapper attributes to require a custom template.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertSame(
                'Repeated embed wrapper attributes require a custom control template.',
                $invalidArgumentException->getMessage(),
            );
        }

        $html = $form->embedMany(path: 'comments', blueprint: CommentForm::class)
            ->apply($options)
            ->template('repeat/comments')
            ->render();
        static::assertStringContainsString('<section id="comments-wrapper" data-heading="&lt;Comments&gt;"', $html);
        static::assertStringContainsString('name="comments[2][body]"', $html);
        static::assertStringContainsString('<h2>Discussion &lt;comments&gt;</h2>', $html);
        $form->close();
    }

    public function testThemeWrapperAttributesRequireACustomTemplate(): void
    {
        $form = $this->helper()
            ->form()
            ->theme(new Theme(defaults: [
                'embed-many.control' => ['class' => 'comments'],
            ]))
            ->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Repeated embed wrapper attributes require a custom control template.');
        $form->embedMany(path: 'comments', blueprint: CommentForm::class)->keys([])->render();
    }

    public function testOmittedWrapperAttributesDoNotReserveIdsOrRequireATemplate(): void
    {
        $form = $this->helper()
            ->form()
            ->theme(new Theme(defaults: [
                'embed-many.control' => ['id' => 'unused-wrapper', 'class' => 'comments'],
            ]))
            ->bind();
        $form->open();
        static::assertSame(
            '',
            $form->embedMany(path: 'comments', blueprint: CommentForm::class)
                ->keys([])
                ->attributes(['id' => null, 'class' => null, 'hidden' => false])
                ->render(),
        );
        static::assertStringContainsString(
            'id="unused-wrapper"',
            $form->control('other')->id('unused-wrapper')->render(),
        );
        $form->close();
    }
}
