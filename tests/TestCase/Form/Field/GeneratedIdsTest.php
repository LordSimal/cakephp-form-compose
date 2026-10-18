<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\View\Form\ArrayContext;
use Dom\Element;
use FormCompose\Form\FormIds;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks stable default identities across paths, feedback, helpers, and views.
 */
class GeneratedIdsTest extends FormTestCase
{
    public function testDistinctPathsAndFeedbackKeepUniqueAssociatedIds(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['bad' => 'Fix title']]]))
            ->bind();
        $first = $form->field('author.name')->resolve();
        static::assertSame($first->state->id, $form->field('author.name')->resolve()->state->id);
        static::assertNotSame($first->state->id, $form->field('author-name')->resolve()->state->id);
        $html = $form->open();
        foreach (['author.name', 'author-name', 'title', 'title.help', 'title.errors', 'title-help'] as $path) {
            $html .= $form->control($path)->help('Help')->render();
        }

        $html .= $form->control('status')
            ->type('radio')
            ->choices(['a' => 'A'])
            ->render();
        $html .= $form->control('status.a')->render() . $form->close();
        $xpath = $this->xpath($html);
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $element) {
            static::assertInstanceOf(Element::class, $element);
            $ids[] = $element->getAttribute('id');
        }

        static::assertSame($ids, array_values(array_unique($ids)));
        foreach ($xpath->query('//label') as $label) {
            static::assertInstanceOf(Element::class, $label);
            $target = $label->getAttribute('for');
            static::assertNotNull($target);
            static::assertSame(1, $xpath->query('//*[@id="' . $target . '"]')->length);
        }

        foreach ($xpath->query('//*[@aria-describedby]') as $input) {
            static::assertInstanceOf(Element::class, $input);
            $descriptions = $input->getAttribute('aria-describedby');
            static::assertNotNull($descriptions);
            foreach (explode(' ', $descriptions) as $id) {
                static::assertSame(1, $xpath->query('//*[@id="' . $id . '"]')->length);
            }
        }
    }

    public function testHelpersAndPostActionsShareTheViewSequenceAndExplicitReservations(): void
    {
        $first = $this->helper();
        $second = new FormHelper($first->getView());
        static::assertSame(
            'form-compose-1',
            $first->form()->id('form-compose-1')->bind()->binding->id,
        );
        static::assertSame('form-compose-2', $second->form()->bind()->binding->id);
        static::assertStringContainsString(
            'id="form-compose-3"',
            $first->postButton(label: 'Delete', url: '/delete')->render(),
        );
        static::assertSame('form-compose-4', $second->form()->bind()->binding->id);
        static::assertSame('form-compose-1', $this->helper()->form()->bind()->binding->id);
    }

    public function testApplicationsCanSpecializeFormNamingThroughBinding(): void
    {
        $helper = new class($this->helper()->getView()) extends FormHelper {
            protected function createFormIds(): FormIds
            {
                return new class($this->getView()) extends FormIds {
                    public function allocate(?string $id = null): string
                    {
                        return parent::allocate($id ?? 'application-form');
                    }
                };
            }
        };
        static::assertSame('application-form', $helper->form()->bind()->binding->id);
    }

    public function testDuplicateExplicitFormIdsAreRejectedAcrossHelpers(): void
    {
        $helper = $this->helper();
        $helper->form()->id('editor')->bind();
        $this->expectException(LogicException::class);
        new FormHelper($helper->getView())->form()
            ->id('editor')
            ->bind();
    }

    /**
     * Supply invalid HTML form identities.
     *
     * @return array<string, array{string}>
     */
    public static function invalidIds(): array
    {
        return ['empty' => [''], 'space' => ['two words'], 'newline' => ["a\nb"]];
    }

    #[DataProvider('invalidIds')]
    public function testInvalidFormIdsAreRejected(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->form()->id($id)->bind();
    }

    public function testExplicitFieldIdsArePreservedAndStillRejectDuplicates(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        static::assertStringContainsString(
            'id="application-field"',
            $form->control('author.name')->id('application-field')->render(),
        );
        $this->expectException(LogicException::class);
        $form->control('other')->id('application-field')->render();
    }
}
