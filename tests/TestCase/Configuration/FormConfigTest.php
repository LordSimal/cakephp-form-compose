<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Configuration;

use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Test\Fixture\Authoring\TitleForm;
use FormCompose\Test\FormTestCase;

/** Checks immutable form presets through complete rendering, including nested buttons and layout selection. */
class FormConfigTest extends FormTestCase
{
    public function testFluentPresetsConfigureLayoutsAndNamedButtonsWithoutConversion(): void
    {
        $preset = FormConfig::builder()
            ->blueprint(TitleForm::class)
            ->template('forms/editor')
            ->submit('Save', config: ButtonConfig::builder()->name('intent')->value('save'));
        $html = $this->helper()->form(config: $preset)->render();
        static::assertStringContainsString('data-form-layout', $html);
        static::assertSame('save', $this->xpath($html)->evaluate('string(//button[text()="Save"]/@value)'));
        static::assertSame('intent', $this->xpath($html)->evaluate('string(//button[text()="Save"]/@name)'));

        $withoutSubmit = $preset->withoutSubmit();
        $withoutHtml = $this->helper()->form(config: $withoutSubmit)->render();
        static::assertSame(0.0, $this->xpath($withoutHtml)->evaluate('count(//button[text()="Save"])'));
        static::assertSame('Save', $preset->submit);
        static::assertSame('forms/editor', $preset->presentation->templates['layout']);
    }
}
