<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Builder;

use Cake\View\Form\ArrayContext;
use FormCompose\Builder\PresentationBuilder;
use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\ChoiceConfig;
use FormCompose\Configuration\ChoiceGroupConfig;
use FormCompose\Configuration\EmbedConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Configuration\SummaryConfig;
use FormCompose\Form\Field\FieldSpec;
use FormCompose\Rendering\Theme;
use FormCompose\Test\CompositionTestCase;

/** Checks automatic snapshot capture at public authoring boundaries without terminal calls. */
class InlineConfigurationTest extends CompositionTestCase
{
    public function testFieldSpecsAndControlsCaptureInlineBuildersImmediately(): void
    {
        $settings = FieldConfig::builder()->label('Currency')->choices(['EUR' => 'Euro']);
        $spec = new FieldSpec('currency', $settings);
        $form = $this->helper()->form()->bind();
        $control = $form->control('billing_currency', config: $settings);
        $settings->label('Changed')->choices(['USD' => 'US dollar']);
        $html = $form->open() . $form->controls([$spec])->render() . $control->render() . $form->close();
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//option[@value="EUR"])'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//option[@value="USD"])'));
        static::assertStringNotContainsString('Changed', $html);
    }

    public function testNestedFormSettingsAndHelperInputAreCapturedWithoutConfigCalls(): void
    {
        $summary = SummaryConfig::builder()->title('Original summary');
        $button = ButtonConfig::builder()->attributes(['class' => 'original-button']);
        $settings = FormConfig::builder()
            ->method('patch')
            ->blueprint('profile')
            ->errorSummary($summary)
            ->submitConfig($button);
        $summary->title('Changed summary');
        $button->attributes(['class' => 'changed-button']);
        $builder = $this->helper()->form(new ArrayContext([
            'errors' => ['email' => ['invalid' => 'Invalid email']],
        ]), config: $settings);
        $settings->method('delete')->blueprint(null);
        $html = $builder->render();
        static::assertStringContainsString('Original summary', $html);
        static::assertStringNotContainsString('Changed summary', $html);
        static::assertSame('PATCH', $this->xpath($html)->evaluate('string(//input[@name="_method"]/@value)'));
        static::assertSame('original-button', $this->xpath($html)->evaluate('string(//button/@class)'));
    }

    public function testActionsCaptureBothTheirSettingsAndTheirNestedFormBuilder(): void
    {
        $formSettings = FormConfig::builder()->id('original-action')->method('patch');
        $settings = ActionConfig::builder()->form($formSettings)->data(['version' => 1]);
        $formSettings->id('changed-action')->method('delete');
        $action = $this->helper()->postButton('Archive', '/archive', config: $settings);
        $settings->data(['version' => 2]);
        $html = $action->render();
        static::assertSame('original-action', $this->xpath($html)->evaluate('string(//form/@id)'));
        static::assertSame('PATCH', $this->xpath($html)->evaluate('string(//input[@name="_method"]/@value)'));
        static::assertSame('1', $this->xpath($html)->evaluate('string(//input[@name="version"]/@value)'));
    }

    public function testRepeatedOccurrencesCapturePresentationWhileSelectionRemainsEditable(): void
    {
        $form = $this->helper(['reviewers' => [2 => ['email' => 'reviewer@example.test']]])
            ->form()
            ->bind();
        $settings = EmbedConfig::builder()->label('Original reviewers');
        $rows = $form->embedMany('reviewers', 'profile', config: $settings)->keys([7]);
        $settings->label('Changed reviewers');
        $rows->keys([2]);
        $html = $form->open() . $rows->render() . $form->close();
        static::assertSame('Original reviewers', $rows->config()->label);
        static::assertStringContainsString('reviewers[2][email]', $html);
        static::assertStringNotContainsString('reviewers[7]', $html);
    }

    public function testInlineChoiceAndThemeBuildersAreCapturedAtConfigurationTime(): void
    {
        $choice = ChoiceConfig::builder('EUR', 'Euro');
        $group = ChoiceGroupConfig::builder('Currencies', [$choice]);
        $theme = Theme::builder()->defaults('input', ['class' => 'original-theme']);
        $builder = $this->helper()->form()->theme($theme);
        $theme->defaults('input', ['class' => 'changed-theme']);
        $form = $builder->bind();
        $control = $form->control('currency')->choices([$group]);
        $choice->label('Changed label');
        $group->attributes(['data-changed' => 'yes']);
        $html = $form->open() . $control->render() . $form->close();
        static::assertSame('original-theme', $this->xpath($html)->evaluate('string(//select/@class)'));
        static::assertSame('Euro', $this->xpath($html)->evaluate('string(//option[@value="EUR"])'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//optgroup/@data-changed)'));
    }

    public function testPresentationAndDefaultButtonBuildersNeedNoExplicitConversion(): void
    {
        $presentation = new PresentationBuilder()->attributes(['autocomplete' => 'off']);
        $settings = new FieldConfig(type: 'email')->withPresentation($presentation);
        $presentation->attributes(['autocomplete' => 'on']);
        $button = ButtonConfig::builder()->name('intent')->value('save');
        $builder = $this->helper()->form()->blueprint('profile')->submit('Save', config: $button);
        $button->value('changed');
        $form = $builder->bind();
        $html =
            $form->open()
            . $form->control('email', config: $settings)->render()
            . $form->submit('Save', config: $button)->render()
            . $form->close();
        static::assertSame('off', $this->xpath($html)->evaluate('string(//input[@name="email"]/@autocomplete)'));
        static::assertSame('changed', $this->xpath($html)->evaluate('string(//button/@value)'));
        static::assertSame('save', $form->binding->config->submitConfig->value);
    }
}
