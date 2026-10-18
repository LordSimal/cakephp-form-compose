<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use FormCompose\Test\Fixture\Composition\PostForm;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/** Verifies presentation overrides preserve unique identities across composed blueprint occurrences. */
class BlueprintPresentationTest extends CompositionTestCase
{
    public function testSharedIdsAreRejectedBeforeComposedInputsAreRegistered(): void
    {
        $form = $this->helper()->form()->blueprint(PostForm::class)->blueprint(AuthorForm::class)->bind();
        $opening = $form->open();
        $this->expectOutputString('');
        try {
            $form->blueprint()->attributes(['id' => 'details'])->render();
            static::fail('Expected a shared blueprint ID to be rejected.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('multiple blueprints', $invalidArgumentException->getMessage());
        }

        $html = $opening . $form->blueprint()->render() . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(0.0, $xpath->evaluate('count(//*[@id="details"])'));
        static::assertSame(1.0, $xpath->evaluate('count(//input[@name="title"])'));
        static::assertSame(1.0, $xpath->evaluate('count(//input[@name="first_name"])'));
    }

    /** @return array<string, array{array<string, mixed>}> Shared classes and explicitly omitted IDs. */
    public static function sharedAttributes(): array
    {
        return [
            'classes only' => [['class' => 'details']],
            'null removes the ID' => [['class' => 'details', 'id' => null]],
            'false removes the ID' => [['class' => 'details', 'id' => false]],
        ];
    }

    /** @param array<string, mixed> $attributes Shared presentation without an emitted ID. */
    #[DataProvider('sharedAttributes')]
    public function testSharedClassesAndOmittedIdsRemainSupported(array $attributes): void
    {
        $form = $this->helper()->form()->blueprint(PostForm::class)->blueprint(AuthorForm::class)->bind();
        $html = $form->open() . $form->blueprint()->attributes($attributes)->render() . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//section[@data-author][@class="details"])'));
        static::assertSame(0.0, $xpath->evaluate('count(//section[@data-author][@id])'));
    }

    public function testAnExplicitBlueprintCanHaveItsOwnIdWithinAComposedForm(): void
    {
        $form = $this->helper()->form()->blueprint(PostForm::class)->blueprint(AuthorForm::class)->bind();
        $html =
            $form->open()
            . $form->blueprint(AuthorForm::class, at: 'billing')->attributes(['id' => 'billing-details'])->render()
            . $form->blueprint()->render()
            . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//section[@id="billing-details"])'));
        static::assertSame(
            1.0,
            $xpath->evaluate('count(//section[@id="billing-details"]//input[@name="billing[first_name]"])'),
        );
        static::assertSame(1.0, $xpath->evaluate('count(//input[@name="title"])'));
    }

    public function testASingleSelectedBlueprintStillAcceptsAnOccurrenceId(): void
    {
        $form = $this->helper()->form()->blueprint(AuthorForm::class)->bind();
        $html = $form->open() . $form->blueprint()->attributes(['id' => 'author-details'])->render() . $form->close();
        static::assertSame(1.0, $this->xpath($html)->evaluate('count(//section[@id="author-details"])'));
    }
}
