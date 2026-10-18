<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Builder;

use Cake\View\Form\ArrayContext;
use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\ChoiceConfig;
use FormCompose\Configuration\ChoiceGroupConfig;
use FormCompose\Configuration\EmbedConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Configuration\SummaryConfig;
use FormCompose\Form\Field\FieldSpec;
use FormCompose\Rendering\Theme;
use FormCompose\Test\CompositionTestCase;

/** Checks reusable immutable settings across field, form, action, choice, and presentation boundaries. */
class InlineConfigurationTest extends CompositionTestCase
{
    public function testFieldSpecsAndControlsReuseImmutableConfigurations(): void
    {
        $settings = FieldConfig::builder()->label('Currency')->choices(['EUR' => 'Euro']);
        $spec = new FieldSpec('currency', $settings);
        $form = $this->helper()->form()->bind();
        $control = $form->control('billing_currency', config: $settings);
        $settings = $settings->label('Changed')->choices(['USD' => 'US dollar']);
        static::assertSame(['USD' => 'US dollar'], $settings->choices);
        $html = $form->open() . $form->controls([$spec])->render() . $control->render() . $form->close();
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//option[@value="EUR"])'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//option[@value="USD"])'));
        static::assertStringNotContainsString('Changed', $html);
    }

    public function testNestedFormSettingsRemainIndependentWhenDerivingNewConfigurations(): void
    {
        $summary = SummaryConfig::builder()->title('Original summary');
        $button = ButtonConfig::builder()->attributes(['class' => 'original-button']);
        $settings = FormConfig::builder()
            ->method('patch')
            ->blueprint('profile')
            ->errorSummary($summary)
            ->submitConfig($button);
        $summary = $summary->title('Changed summary');
        static::assertSame('Changed summary', $summary->title);
        $button = $button->attributes(['class' => 'changed-button']);
        static::assertSame(['class' => 'original-button changed-button'], $button->presentation->attributes);
        $builder = $this->helper()->form(new ArrayContext([
            'errors' => ['email' => ['invalid' => 'Invalid email']],
        ]), config: $settings);
        $settings = $settings->method('delete')->blueprint(null);
        static::assertSame('delete', $settings->method);
        static::assertSame([], $settings->blueprints);
        $html = $builder->render();
        static::assertStringContainsString('Original summary', $html);
        static::assertStringNotContainsString('Changed summary', $html);
        static::assertSame('PATCH', $this->xpath($html)->evaluate('string(//input[@name="_method"]/@value)'));
        static::assertSame('original-button', $this->xpath($html)->evaluate('string(//button/@class)'));
    }

    public function testActionAndNestedFormConfigurationsAreIndependent(): void
    {
        $formSettings = FormConfig::builder()->id('original-action')->method('patch');
        $settings = ActionConfig::builder()->form($formSettings)->data(['version' => 1]);
        $formSettings = $formSettings->id('changed-action')->method('delete');
        static::assertSame('changed-action', $formSettings->id);
        $action = $this->helper()->postButton('Archive', '/archive', config: $settings);
        $settings = $settings->data(['version' => 2]);
        static::assertSame(['version' => 2], $settings->data);
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
        $settings = $settings->label('Changed reviewers');
        static::assertSame('Changed reviewers', $settings->label);
        $rows->keys([2]);
        $html = $form->open() . $rows->render() . $form->close();
        static::assertSame('Original reviewers', $rows->config()->label);
        static::assertStringContainsString('reviewers[2][email]', $html);
        static::assertStringNotContainsString('reviewers[7]', $html);
    }

    public function testDerivedChoicesAndThemesDoNotChangeConfiguredControls(): void
    {
        $choice = new ChoiceConfig('EUR', 'Euro');
        $group = new ChoiceGroupConfig('Currencies', [$choice]);
        $theme = Theme::builder()->defaults('input', ['class' => 'original-theme']);
        $builder = $this->helper()->form()->theme($theme);
        $theme = $theme->defaults('input', ['class' => 'changed-theme']);
        static::assertSame('original-theme changed-theme', $theme->attributes('input', 'text')->values['class']);
        $form = $builder->bind();
        $control = $form->control('currency')->choices([$group]);
        $choice = $choice->label('Changed label');
        static::assertSame('Changed label', $choice->label);
        $group = $group->attributes(['data-changed' => 'yes']);
        static::assertSame(['data-changed' => 'yes'], $group->attributes);
        $html = $form->open() . $control->render() . $form->close();
        static::assertSame('original-theme', $this->xpath($html)->evaluate('string(//select/@class)'));
        static::assertSame('Euro', $this->xpath($html)->evaluate('string(//option[@value="EUR"])'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//optgroup/@data-changed)'));
    }

    public function testPresentationAndDefaultButtonsUseConfigurationsDirectly(): void
    {
        $presentation = PresentationConfig::builder()->attributes(['autocomplete' => 'off']);
        $settings = new FieldConfig(type: 'email')->presentation($presentation);
        $presentation = $presentation->attributes(['autocomplete' => 'on']);
        static::assertSame(['autocomplete' => 'on'], $presentation->attributes);
        $button = ButtonConfig::builder()->name('intent')->value('save');
        $builder = $this->helper()->form()->blueprint('profile')->submit('Save', config: $button);
        $button = $button->value('changed');
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
