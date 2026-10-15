<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\ORM\Entity;
use FormCompose\Test\AssociationTestCase;

/**
 * Verifies belongsTo, hasOne, hasMany, and junction fields using CakePHP contexts.
 */
class NestedAssociationTest extends AssociationTestCase
{
    public function testBelongsToUsesConventionalOptionsAndBoundForeignKeys(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('authors', [1 => 'Grace', 2 => 'Ada']);
        $form = $helper->bind($this->article(), ['context' => ['table' => $this->articles]]);
        $form->open();

        $html = $form->control('author_id') . $form->control('comments.0.author_id');
        $form->close();
        $xpath = $this->xpath($html);

        static::assertSame(2.0, $xpath->evaluate('count(//select)'));
        static::assertSame(2.0, $xpath->evaluate('count(//option[@value="2"][@selected])'));
        static::assertSame(0.0, $xpath->evaluate('count(//select[@multiple])'));
        static::assertSame('Author', $xpath->evaluate('string(//label)'));
        static::assertStringContainsString('name="comments[0][author_id]"', $html);
    }

    public function testHasOneUsesTheAssociatedSchemaValidatorAndEntityErrors(): void
    {
        $article = $this->article();
        /** @var mixed $details */
        $details = $article->get('details');
        static::assertInstanceOf(Entity::class, $details);
        $details->setError('summary', ['invalid' => 'Fix the summary']);
        $form = $this->helper()->bind($article, ['context' => ['table' => $this->articles]]);
        $field = $form->field('details.summary');

        static::assertSame('Summary', $field->state->value);
        static::assertSame('details[summary]', $field->state->name);
        static::assertSame(60, $field->state->constraints['maxlength']);
        static::assertTrue($field->state->constraints['required']);
        static::assertSame(['Fix the summary'], $field->state->messages);
        static::assertSame('checkbox', $form->field('details.is_public')->state->type);
        $form->open();
        $html = $field->control();
        $form->close();
        static::assertStringContainsString('name="details[summary]"', $html);
        static::assertStringContainsString('Fix the summary', $html);
    }

    public function testHasManyResolvesIndexedFieldsAndProtectsExistingPrimaryKeys(): void
    {
        $article = $this->article();
        /** @var list<Entity> $comments */
        $comments = $article->get('comments');
        $comment = $comments[0];
        $comment->setError('body', ['invalid' => 'Fix this comment']);

        $form = $this->helper(['comments' => [['body' => 'Submitted comment']]])->bind($article, [
            'context' => ['table' => $this->articles],
        ]);
        $field = $form->field('comments.0.body');
        static::assertSame('textarea', $field->state->type);
        static::assertSame('Submitted comment', $field->state->value);
        static::assertSame(40, $field->state->constraints['maxlength']);
        static::assertTrue($field->state->constraints['required']);
        static::assertSame(['Fix this comment'], $field->state->messages);
        $form->open();
        $html = $form->control('comments.0.id') . $field->control();
        $form->close();

        static::assertSame(
            1.0,
            $this->xpath($html)->evaluate('count(//input[@type="hidden"][@name="comments[0][id]"])'),
        );
        static::assertStringContainsString('value="10"', $html);
        static::assertStringContainsString('name="comments[0][body]"', $html);
        static::assertStringContainsString('Submitted comment', $html);
    }

    public function testMissingAssociatedRecordsStillResolveTheirSchema(): void
    {
        $article = new Entity([], ['source' => 'Articles']);
        $form = $this->helper()->bind($article, ['context' => ['table' => $this->articles]]);

        static::assertNull($form->field('details.summary')->state->value);
        static::assertSame(60, $form->field('details.summary')->state->constraints['maxlength']);
        static::assertSame('textarea', $form->field('comments.0.body')->state->type);
        static::assertSame('select', $form->field('tags._ids')->state->type);
    }

    public function testJunctionFieldsResolveTheirValueAndSchema(): void
    {
        $article = $this->article();
        /** @var list<Entity> $tags */
        $tags = $article->get('tags');
        /** @var mixed $join */
        $join = $tags[0]->get('_joinData');
        static::assertInstanceOf(Entity::class, $join);
        $join->setError('priority', ['invalid' => 'Invalid priority']);
        $form = $this->helper()->bind($article, ['context' => ['table' => $this->articles]]);
        $field = $form->field('tags.0._joinData.priority');

        static::assertSame('number', $field->state->type);
        static::assertSame(3, $field->state->value);
        static::assertSame('1', $field->state->constraints['step']);
        static::assertSame(['Invalid priority'], $field->state->messages);
        $form->open();
        $html = $field->control();
        $form->close();
        static::assertStringContainsString('name="tags[0][_joinData][priority]"', $html);
    }

    public function testExplicitTypesAndLabelsRemainAuthoritative(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('authors', [1 => 'Unwanted']);
        $form = $helper->bind($this->article(), ['context' => ['table' => $this->articles]]);
        $field = $form->field('author_id', ['type' => 'number', 'label' => 'Owner']);

        static::assertSame('number', $field->state->type);
        static::assertSame('Owner', $field->state->labelText());
        static::assertArrayNotHasKey('options', $field->state->options);
        static::assertSame('text', $form->field('comments.0.id', ['type' => 'text'])->state->type);
    }

    public function testBelongsToRecordFieldsUseTheirTargetMetadataAndErrors(): void
    {
        $article = $this->article();
        /** @var Entity $author */
        $author = $article->get('author');
        $author->setError('name', ['invalid' => 'Fix the author name']);

        $form = $this->helper()->bind($article, ['context' => ['table' => $this->articles]]);
        $field = $form->field('author.name');

        static::assertSame('Ada', $field->state->value);
        static::assertSame('author[name]', $field->state->name);
        static::assertSame(60, $field->state->constraints['maxlength']);
        static::assertSame(['Fix the author name'], $field->state->messages);
        static::assertSame('hidden', $form->field('author.id')->state->type);
    }
}
