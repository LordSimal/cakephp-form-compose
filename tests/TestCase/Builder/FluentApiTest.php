<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Builder;

use ArrayObject;
use FormCompose\Blueprint\BlueprintView;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Configuration\ChoiceConfig;
use FormCompose\Configuration\ChoiceGroupConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use LogicException;

/** Exercises mutable authoring, immutable snapshots, and explicit rendering boundaries. */
class FluentApiTest extends FormTestCase
{
    public function testReusableSnapshotsDoNotTrackLaterBuilderChanges(): void
    {
        $builder = FieldConfig::builder(new FieldConfig(type: 'email', label: 'Email address'))
            ->required()
            ->attributes(['autocomplete' => 'email']);
        $email = $builder->config();
        $builder->label('Work email')->disabled();

        $form = $this->helper()->form()->bind();
        $html =
            $form->open()
            . $form->control('personal', config: $email)->render()
            . $form->control('work', config: $builder->config())->render()
            . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(
            'Email address',
            $xpath->evaluate('string(//label[@for="' . $form->field('personal', $email)->resolve()->state->id . '"])'),
        );
        static::assertSame(0.0, $xpath->evaluate('count(//input[@name="personal"]/@disabled)'));
        static::assertSame(1.0, $xpath->evaluate('count(//input[@name="work"]/@disabled)'));
        static::assertSame('email', $xpath->evaluate('string(//input[@name="personal"]/@autocomplete)'));
    }

    public function testControlResolvesItsFinalSettingsOnlyWhenRendered(): void
    {
        /** @var ArrayObject<int, string> $calls */
        $calls = new ArrayObject();
        $form = $this->helper(['email' => 'reader@example.test'])->form()->bind();
        $control = $form->control('email')->label(static function () use ($calls): string {
            $calls->append('Email');

            return 'Email';
        });
        static::assertCount(0, $calls);
        $control->value(null)->required();
        $form->open();
        $html = (string)$control;
        static::assertCount(1, $calls);
        static::assertSame('', $this->xpath($html)->evaluate('string(//input/@value)'));
        static::assertSame(1.0, $this->xpath($html)->evaluate('count(//input/@required)'));
        $form->close();
    }

    public function testFieldResolutionAndConfigurationDoNotReserveInputIds(): void
    {
        $form = $this->helper()->form()->bind();
        $builder = $form->field('title')->label('Original');
        $field = $builder->resolve();
        $builder->label('Changed')->value('Changed');
        static::assertSame('Original', $field->state->labelText());
        static::assertNull($field->state->value);
        $field->attributes();
        $form->open();
        static::assertStringContainsString('Original', $field->control());
        $form->close();
    }

    public function testPresentationMergesAndReplacementsSurviveSnapshotReuse(): void
    {
        $base = FieldConfig::builder()
            ->attributes(['class' => 'base', 'data-old' => 'yes'])
            ->templateData(['old' => 1, 'retained' => 2])
            ->partAttributes('label', ['class' => 'base-label'])
            ->config();
        $layer = FieldConfig::builder()
            ->replaceAttributes(['class' => 'replacement'])
            ->attributes(['class' => 'extra'])
            ->replaceTemplateData(['fresh' => 3])
            ->replacePartAttributes('label', ['class' => 'new-label'])
            ->config();
        $merged = $base->merge($layer)->presentation;
        static::assertSame('replacement extra', $merged->attributes['class']);
        static::assertArrayNotHasKey('data-old', $merged->attributes);
        static::assertSame(['fresh' => 3], $merged->templateData);
        static::assertSame(['class' => 'new-label'], $merged->partAttributes['label']);
        static::assertSame(['old' => 1, 'retained' => 2], $base->presentation->templateData);
        static::assertNull(new PresentationConfig(variant: 'compact')->withVariant(null)->variant);
    }

    public function testChoiceAndThemeBuildersRenderTypedChoiceMetadata(): void
    {
        $choice = ChoiceConfig::builder('a', 'Alpha')
            ->attributes(['data-code' => 'alpha'])
            ->id('choice-alpha')
            ->config();
        $group = ChoiceGroupConfig::builder('Letters', [$choice])->attributes(['data-group' => 'letters'])->config();
        $theme = Theme::builder()->defaults('input', ['class' => 'themed'])->build();
        $form = $this->helper()->form()->theme($theme)->bind();
        $html = $form->open() . $form->control('letter')->choices([$group])->value('a')->render() . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame('themed', $xpath->evaluate('string(//select/@class)'));
        static::assertSame('letters', $xpath->evaluate('string(//optgroup/@data-group)'));
        static::assertSame('alpha', $xpath->evaluate('string(//option[@selected]/@data-code)'));
    }

    public function testChoiceEditsDoNotChangeEarlierSnapshots(): void
    {
        $builder = ChoiceConfig::builder('a', 'Original');
        $original = $builder->config();
        $updated = $builder->label('Edited')->disabled()->config();
        static::assertSame('Original', $original->label);
        static::assertSame([], $original->attributes);
        static::assertSame('Edited', $updated->label);
        static::assertTrue($updated->attributes['disabled']);
    }

    public function testOmittedScopedControlsKeepTheFullFluentApi(): void
    {
        $form = $this->helper()->form()->bind();
        $definition = new FormDefinition([new FieldDefinition('title')])->remove('title');
        $view = new BlueprintView($form, $definition);
        static::assertSame(
            '',
            (string)$view->control('title')->label('Unused')->required()->attributes(['class' => 'unused']),
        );
        static::assertFalse($view->has('title'));
        $this->expectException(InvalidArgumentException::class);
        $view->control('title')->resolve();
    }

    public function testScopedBatchFailureRollsBackEarlierControls(): void
    {
        $form = $this->helper()->form()->bind();
        $view = new BlueprintView($form, new FormDefinition([new FieldDefinition('title')]));
        $form->open();
        try {
            $view->controls(['title', 'typo'])->render();
            static::fail('Unknown scoped fields must fail the batch.');
        } catch (InvalidArgumentException) {
            static::assertStringContainsString('name="title"', $view->control('title')->render());
        }

        $form->close();
    }

    public function testForeignControlsAreRejectedBeforeRenderingTheBatch(): void
    {
        $form = $this->helper()->form()->bind();
        $other = $this->helper()->form()->bind();
        $form->open();
        try {
            $form->controls(['title', $other->control('foreign')])->render();
            static::fail('Controls from another session must be rejected.');
        } catch (InvalidArgumentException) {
            static::assertStringContainsString('name="title"', $form->control('title')->render());
        }

        $form->close();
    }

    public function testStoredRenderableStillRequiresAnOpenForm(): void
    {
        $form = $this->helper()->form()->bind();
        $control = $form->control('title');
        $form->open();
        $form->close();
        $this->expectException(LogicException::class);
        $control->render();
    }
}
