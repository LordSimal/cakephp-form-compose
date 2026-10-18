<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Builder;

use Cake\View\Form\ArrayContext;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Configuration\SummaryConfig;
use FormCompose\Test\Fixture\Authoring\BodyForm;
use FormCompose\Test\Fixture\Authoring\EmptyForm;
use FormCompose\Test\FormTestCase;

/** Exercises fluent configuration through rendered HTML, including implicit rendering and reusable overlays. */
class RenderingContractTest extends FormTestCase
{
    public function testStringConversionBindsAnEmptyBlueprintWithoutADefaultButton(): void
    {
        $html = (string)$this->helper()->form()->blueprint(EmptyForm::class)->withoutSubmit();
        static::assertSame(1.0, $this->xpath($html)->evaluate('count(//form)'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//button)'));
    }

    public function testStringConversionRendersAnIndependentProtectedAction(): void
    {
        $html = (string)$this->helper()->postLink('Delete <row>', '/delete')->method('delete');
        static::assertStringContainsString('Delete &lt;row&gt;', $html);
        static::assertSame('DELETE', $this->xpath($html)->evaluate('string(//input[@name="_method"]/@value)'));
        static::assertSame('/delete', $this->xpath($html)->evaluate('string(//form/@action)'));
    }

    public function testStringConversionRendersSelectedRepeatedRows(): void
    {
        $form = $this->helper(['comments' => [2 => ['body' => '<Comment>']]])
            ->form()
            ->bind();
        $form->open();

        $html = (string)$form->embedMany('comments', BodyForm::class)->keys([2]);
        static::assertSame('comments[2][body]', $this->xpath($html)->evaluate('string(//input/@name)'));
        static::assertSame('<Comment>', $this->xpath($html)->evaluate('string(//input/@value)'));
        $form->close();
    }

    public function testSummaryOverlaysRenderEscapedTitlesAndLinksThroughStringConversion(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]))
            ->bind();
        $form->open();
        $form->control('title')->render();
        $html = (string)$form->errorSummary()->apply(
            SummaryConfig::builder()->title('<Review>')->attributes(['class' => 'review-errors']),
        );
        static::assertStringContainsString('&lt;Review&gt;', $html);
        static::assertStringContainsString('class="review-errors"', $html);
        static::assertStringContainsString('Fix title', $html);
        static::assertSame('#form-compose-1-title', $this->xpath($html)->evaluate('string(//a/@href)'));
        $form->close();
    }

    public function testButtonTypeSetterChangesTheRenderedNativeButton(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = (string)$form->submit('Preview')->type('button');
        static::assertSame('button', $this->xpath($html)->evaluate('string(//button/@type)'));
        static::assertSame('Preview', $this->xpath($html)->evaluate('string(//button)'));
        $form->close();
    }

    public function testEncodingSetterControlsTheOpeningForm(): void
    {
        $form = $this->helper()->form()->encoding('ISO-8859-1')->bind();
        $html = $form->open() . $form->close();
        static::assertSame('iso-8859-1', $this->xpath($html)->evaluate('string(//form/@accept-charset)'));
    }

    public function testPartOverlaysMergePresentationWithoutChangingFieldConfiguration(): void
    {
        $form = $this->helper()->form()->bind();
        $field = $form->field('title')->label('Article <title>')->resolve();
        $label = $field->label()->attributes(['class' => 'base', 'data-field' => 'title']);
        $label->apply(PresentationConfig::builder()->attributes(['class' => 'accent']));

        $form->open();
        $html = (string)$label;
        static::assertSame('base accent', $this->xpath($html)->evaluate('string(//label/@class)'));
        static::assertSame('title', $this->xpath($html)->evaluate('string(//label/@data-field)'));
        static::assertStringContainsString('Article &lt;title&gt;', $html);
        static::assertSame([], $field->state->config->presentation->attributes);
        $field->input()->render();
        $form->close();
    }

    public function testCheckboxFallbackAndSchemaDefaultOverridesAffectActualInputs(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['defaults' => ['published' => 'on']]))
            ->bind();
        static::assertSame('on', $form->field('published')->resolve()->state->value);
        $control = $form->control('published')
            ->type('checkbox')
            ->checkedValue('on')
            ->uncheckedValue('off')
            ->schemaDefault(false);
        $form->open();
        $html = (string)$control;
        static::assertSame('off', $this->xpath($html)->evaluate('string(//input[@type="hidden"]/@value)'));
        static::assertSame('on', $this->xpath($html)->evaluate('string(//input[@type="checkbox"]/@value)'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//input/@checked)'));
        $form->close();
    }
}
