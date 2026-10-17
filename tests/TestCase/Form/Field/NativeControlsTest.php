<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use DateTimeImmutable;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

class NativeControlsTest extends FormTestCase
{
    public function testNestedMultipleNamesAndFallbacksRemainValid(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $select = $form->control('comments.0.tags', ['options' => ['a' => 'A'], 'multiple' => true]);
        static::assertStringContainsString('name="comments[0][tags][]"', $select);
        static::assertStringContainsString('type="hidden" name="comments[0][tags]"', $select);
        $checks = $form->control('comments.1.tags', ['type' => 'multicheckbox', 'options' => ['a' => 'A']]);
        static::assertStringContainsString('name="comments[1][tags][]"', $checks);
        $explicit = $form->control('custom', [
            'name' => 'custom[tags][]',
            'options' => ['a' => 'A'],
            'multiple' => true,
        ]);
        static::assertStringNotContainsString('[][]', $explicit);
        $form->close();
    }

    public function testDateTimeObjectsAreFormattedForNativeInputs(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $value = new DateTimeImmutable('2026-10-15 14:23:45');
        static::assertStringContainsString('value="2026-10-15"', $form->control('day', [
            'type' => 'date',
            'value' => $value,
        ]));
        static::assertStringContainsString('value="2026-10"', $form->control('month', [
            'type' => 'month',
            'value' => $value,
        ]));
        static::assertStringContainsString('value="14:23:45"', $form->control('time', [
            'type' => 'time',
            'value' => $value,
        ]));
        static::assertStringContainsString('value="2026-10-15T14:23:45"', $form->control('when', [
            'type' => 'datetime-local',
            'value' => $value,
        ]));
        $form->close();
    }

    public function testNonScalarTextValuesAreRejected(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $form->control('name', ['value' => ['unexpected']]);
    }

    public function testGroupsAndExtendedOptionsEscapeLabelsAndAttributes(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('status', [
            'value' => 'a',
            'options' => ['<Group>' => [['value' => 'a', 'text' => '<Active>', 'data-extra' => '"><unsafe>']]],
        ]);
        static::assertStringContainsString('<optgroup', $html);
        static::assertStringContainsString('label="&lt;Group&gt;"', $html);
        static::assertStringContainsString('&lt;Active&gt;', $html);
        static::assertStringNotContainsString('<unsafe>', $html);
        $form->close();
    }

    public function testDuplicateChoiceValuesStillReceiveUniqueIdsAndLabels(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('status', [
            'type' => 'radio',
            'options' => [
                ['value' => 'a', 'text' => 'First'],
                ['value' => 'a', 'text' => 'Second'],
                ['value' => 'a', 'text' => 'Third'],
            ],
        ]);
        /** @var NodeList<Element>|false $nodes */
        $nodes = $this->xpath($html)->query('//input[@type="radio"]');
        static::assertInstanceOf(NodeList::class, $nodes);
        $ids = [];
        foreach ($nodes as $node) {
            $id = $node->getAttribute('id');
            static::assertNotNull($id);
            $ids[] = $id;
            static::assertStringContainsString('for="' . $id . '"', $html);
        }

        static::assertCount(3, array_unique($ids));
        $form->close();
    }

    public function testDisabledChoicesAndCustomPartAttributes(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('status', [
            'type' => 'radio',
            'disabled' => true,
            'options' => [
                ['value' => 'a', 'text' => 'A', 'disabled' => false],
            ],
        ]);
        static::assertStringContainsString(' disabled', $html);
        static::assertStringNotContainsString('required', $html);
        $form->close();
    }

    public function testNativeBooleanAttributesUsePresenceSemantics(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('tags', ['options' => ['a' => 'A'], 'multiple' => 'multiple']);
        static::assertStringContainsString('name="tags[]"', $html);
        static::assertStringContainsString('multiple="multiple"', $html);
        $form->close();
    }
}
