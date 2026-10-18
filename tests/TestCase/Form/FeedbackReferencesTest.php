<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use FormCompose\Form\FeedbackReferences;
use FormCompose\Rendering\Attributes;
use FormCompose\Test\FormTestCase;
use LogicException;

/** Checks whitespace-separated generated feedback references independently of application-owned IDs. */
class FeedbackReferencesTest extends FormTestCase
{
    public function testMixedWhitespaceReferencesStillRequireTheirGeneratedHelpTarget(): void
    {
        $field = $this->helper()
            ->form()
            ->bind()
            ->field('title')
            ->help('Hint')
            ->resolve();
        $helpId = $field->state->id . '-help';
        $references = new FeedbackReferences();
        $references->record($field, new Attributes(['aria-describedby' => "external\t{$helpId}\nother-external"]));
        try {
            $references->assertResolved([]);
            static::fail('The generated help reference must have a rendered target.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString($helpId, $logicException->getMessage());
            static::assertStringNotContainsString('external', $logicException->getMessage());
        }

        $references->assertResolved([$helpId => true]);
        static::assertSame([$helpId => true], $references->expectations());
    }

    public function testEmptyDescriptionsDoNotRequireGeneratedTargets(): void
    {
        $field = $this->helper()
            ->form()
            ->bind()
            ->field('title')
            ->help('Hint')
            ->resolve();
        foreach (['', " \t\n "] as $description) {
            $references = new FeedbackReferences();
            $references->record($field, new Attributes(['aria-describedby' => $description]));
            $references->assertResolved([]);
            static::assertSame([], $references->expectations());
        }
    }
}
