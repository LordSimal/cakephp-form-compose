<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Configuration;

use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Test\Fixture\Authoring\EmailForm;
use FormCompose\Test\Fixture\Authoring\TitleForm;
use FormCompose\Test\FormTestCase;

/** Checks immutable form presets through complete rendering, including nested buttons and layout selection. */
class FormConfigTest extends FormTestCase
{
    public function testBlueprintSelectionsAppendImmutablyAndCanBeCleared(): void
    {
        $empty = FormConfig::builder();
        $article = $empty->blueprint(TitleForm::class);
        $combined = $article->blueprint(EmailForm::class);
        static::assertSame([], $empty->blueprints);
        static::assertSame([TitleForm::class], $article->blueprints);
        static::assertSame([TitleForm::class, EmailForm::class], $combined->blueprints);
        static::assertSame([], $combined->blueprint(null)->blueprints);

        $html = $this->helper()->form(config: $combined)->render();
        static::assertStringContainsString('name="title"', $html);
        static::assertStringContainsString('type="email"', $html);
        static::assertSame([TitleForm::class, EmailForm::class], $combined->blueprints);
    }

    public function testMergingPresetsOverlaysTheExplicitSelectionAndRetainsUnspecifiedSelections(): void
    {
        $original = FormConfig::builder()->blueprint(TitleForm::class);
        static::assertSame([TitleForm::class], $original->merge(FormConfig::builder()->id('editor'))->blueprints);
        static::assertSame(
            [EmailForm::class],
            $original->merge(FormConfig::builder()->blueprint(EmailForm::class))->blueprints,
        );
        static::assertSame([], $original->merge(FormConfig::builder()->blueprint(null))->blueprints);
    }

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
