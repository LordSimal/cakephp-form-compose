<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use ArrayIterator;
use Cake\ORM\Entity;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Test\AssociationTestCase;

/**
 * Covers choice defaults, selected identifiers, and feedback for ORM associations.
 */
class AssociationRenderingTest extends AssociationTestCase
{
    public function testBelongsToManyDiscoversOptionsAndSelectsCustomPrimaryKeys(): void
    {
        $helper = $this->helper();
        $form = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->bind();
        $helper->getView()->set('tags', ['php' => 'PHP', 'css' => 'CSS', 'cake' => 'CakePHP']);
        $state = $form->field('tags._ids')->resolve()->state;
        static::assertSame('select', $state->type);
        static::assertTrue($state->constraints['multiple']);
        static::assertSame(['php', 'cake'], $state->value);
        $form->open();
        $html = $form->control('tags._ids')->render();
        $form->close();
        $xpath = $this->xpath($html);

        static::assertSame(1.0, $xpath->evaluate('count(//select[@name="tags[_ids][]"][@multiple])'));
        static::assertSame(2.0, $xpath->evaluate('count(//option[@selected])'));
        static::assertSame(1.0, $xpath->evaluate('count(//option[@value="php"][@selected])'));
        static::assertSame(1.0, $xpath->evaluate('count(//option[@value="cake"][@selected])'));
        static::assertSame('Tags', $xpath->evaluate('string(//label)'));
        static::assertSame(1.0, $xpath->evaluate('count(//input[@type="hidden"][@name="tags[_ids]"][@value=""])'));
    }

    public function testNestedCollectionsUseTheirPropertyForDiscoveryAndLabels(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('tags', new ArrayIterator(['php' => 'PHP', 'cake' => 'CakePHP']));
        $helper->getView()->set('topicTags', ['php' => 'PHP']);
        $form = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->bind();
        static::assertSame(['php', 'cake'], $form->field('comments.0.tags._ids')->resolve()->state->value);
        static::assertSame('Topic Tags', $form->field('topic_tags._ids')->resolve()->state->labelText());
        static::assertSame(['php' => 'PHP'], $form->field('topic_tags._ids')->resolve()->state->config->choices);
        $form->open();
        $html = $form->control('comments.0.tags._ids')->render();
        $form->close();

        static::assertStringContainsString('name="comments[0][tags][_ids][]"', $html);
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//option[@selected])'));
    }

    public function testExplicitChoicesAndPresentationOverrideInferredDefaults(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('tags', ['unwanted' => 'Unwanted']);
        $form = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->bind();
        $form->open();

        $html = $form->control('tags._ids')
            ->choices(['css' => 'CSS'])
            ->value(['css'])
            ->label('Choose topics')
            ->partTemplate('control', 'themes/custom/control')
            ->render();
        $form->close();

        static::assertStringContainsString('data-control="custom"', $html);
        static::assertStringContainsString('Choose topics', $html);
        static::assertSame('css', $this->xpath($html)->evaluate('string(//option[@selected]/@value)'));
        static::assertStringNotContainsString('Unwanted', $html);
        static::assertSame(
            [],
            $form->field('tags._ids')
                ->choices([])
                ->resolve()
                ->state
                ->config
                ->choices,
        );
        static::assertFalse(
            $form->field('tags._ids')
                ->multiple(false)
                ->resolve()
                ->state
                ->constraints['multiple'],
        );
        static::assertSame(
            'hidden',
            $form->field('tags._ids')
                ->type('hidden')
                ->value('php')
                ->resolve()
                ->state
                ->type,
        );
        static::assertArrayNotHasKey(
            'multiple',
            $form->field('tags._ids')
                ->type('hidden')
                ->resolve()
                ->state
                ->constraints,
        );
    }

    public function testCheckboxCollectionsDiscoverChoicesAndKeepAssociationLabels(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('tags', ['php' => 'PHP', 'css' => 'CSS', 'cake' => 'CakePHP']);
        $form = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->bind();
        $definition = new FormDefinition([])->add(
            new FieldDefinition('tags._ids', FieldConfig::builder()->type('multicheckbox')),
        );
        $form->open();
        $html = $form->renderDefinition($definition);
        $form->close();
        $xpath = $this->xpath($html);

        static::assertSame('Tags', $xpath->evaluate('string(//legend)'));
        static::assertSame(3.0, $xpath->evaluate('count(//input[@type="checkbox"][@name="tags[_ids][]"])'));
        static::assertSame(2.0, $xpath->evaluate('count(//input[@type="checkbox"][@checked])'));
    }

    public function testAssociationErrorsAndRequiredStateReachTheIdsControl(): void
    {
        $article = $this->article();
        $article->setError('tags', ['valid' => '<Choose valid tags>']);
        $this->articles->getValidator()->notEmptyArray('tags');
        $form = $this->helper()
            ->form($article)
            ->context(['table' => $this->articles])
            ->bind();
        $field = $form->field('tags._ids')->resolve();
        static::assertSame(['<Choose valid tags>'], $field->state->messages);
        static::assertTrue($field->state->constraints['required']);
        $form->open();
        $html = $field->control();
        $form->close();

        static::assertStringContainsString('&lt;Choose valid tags&gt;', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
        static::assertStringContainsString('aria-describedby="' . $field->state->id . '-errors"', $html);
        static::assertStringContainsString('id="' . $field->state->id . '-errors"', $html);
        static::assertStringContainsString('for="' . $field->state->id . '"', $html);
        static::assertFalse(
            $form->field('tags._ids')
                ->required(false)
                ->resolve()
                ->state
                ->constraints['required'],
        );
    }

    public function testNestedAssociationErrorsUseTheCorrectEntity(): void
    {
        $article = $this->article();
        /** @var list<Entity> $comments */
        $comments = $article->get('comments');
        $comment = $comments[0];
        $comment->setError('tags', ['invalid' => 'Comment tags are invalid']);
        $this->comments->getValidator()->notEmptyArray('tags');
        $form = $this->helper()
            ->form($article)
            ->context(['table' => $this->articles])
            ->bind();

        static::assertSame(
            ['Comment tags are invalid'],
            $form->field('comments.0.tags._ids')->resolve()->state->messages,
        );
        static::assertTrue($form->field('comments.0.tags._ids')->resolve()->state->constraints['required']);
        static::assertSame([], $form->field('tags._ids')->resolve()->state->messages);
    }

    public function testMissingOrNonIterableViewVariablesDoNotBecomeChoices(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('tags', 'not a list');
        $form = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->bind();
        $state = $form->field('tags._ids')->resolve()->state;
        static::assertSame('select', $state->type);
        static::assertFalse($state->config->hasChoices);
        static::assertFalse($form->field('author_id')->resolve()->state->config->hasChoices);
    }

    public function testHasManyIdsSelectorsUseTheSameCollectionConventions(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('comments', [10 => 'Existing comment', 11 => 'Other comment']);
        $form = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->bind();
        static::assertSame([10], $form->field('comments._ids')->resolve()->state->value);
        $form->open();
        $html = $form->control('comments._ids')->render();
        $form->close();

        static::assertSame('10', $this->xpath($html)->evaluate('string(//option[@selected]/@value)'));
        static::assertStringContainsString('name="comments[_ids][]"', $html);
        static::assertStringContainsString('>Comments</label>', $html);
    }
}
