<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\View\Form\ArrayContext;
use FormCompose\Field\FieldDefinition;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

class BindingTest extends FormTestCase
{
    public function testExplicitAndSubmittedEmptyValuesRemainDistinct(): void
    {
        $form = $this->helper([
            'nil' => null,
            'zero' => 0,
            'off' => false,
            'blank' => '',
        ])->bind(new ArrayContext(['data' => ['nil' => 'original']]));
        static::assertNull($form->field('nil')->state->value);
        static::assertSame(0, $form->field('zero')->state->value);
        static::assertFalse($form->field('off')->state->value);
        static::assertSame('', $form->field('blank')->state->value);
        static::assertNull($form->field('missing', ['value' => null, 'default' => 'fallback'])->state->value);
        static::assertSame('fallback', $form->field('missing', ['default' => 'fallback'])->state->value);
    }

    public function testContextInferenceConstraintsAndNestedNames(): void
    {
        $form = $this->helper()->bind([
            'schema' => [
                'title' => ['type' => 'string', 'length' => 30],
                'body' => ['type' => 'text'],
                'active' => ['type' => 'boolean'],
                'price' => ['type' => 'decimal'],
                'count' => ['type' => 'integer'],
            ],
            'required' => ['title' => true],
            'data' => ['title' => 'Hello'],
            'errors' => ['title' => ['invalid' => 'Invalid']],
        ]);
        $title = $form->field('title')->state;
        static::assertSame('Hello', $title->value);
        static::assertSame(['Invalid'], $title->messages);
        static::assertSame(['required' => true, 'maxlength' => 30], $title->constraints);
        static::assertSame('textarea', $form->field('body')->state->type);
        static::assertSame('checkbox', $form->field('active')->state->type);
        static::assertSame('any', $form->field('price')->state->constraints['step']);
        static::assertSame('1', $form->field('count')->state->constraints['step']);
        static::assertSame('comments[0][body]', $form->field('comments.0.body')->state->name);
        static::assertFalse($form->field('title', ['disabled' => true])->state->constraints['required']);
    }

    public function testDefinitionsAreReusableAndInlineOverridesWin(): void
    {
        $base = FieldDefinition::make('email', 'email')->label('Email')->help('Work email');
        $changed = $base->with(['label' => 'Contact']);
        static::assertSame('Email', $base->options['label']);
        $form = $this->helper()->bind();
        static::assertSame('Contact', $form->field($changed)->state->options['label']);
        static::assertSame('Override', $form->field($changed, ['label' => 'Override'])->state->options['label']);
        $form->open(['url' => '/articles/save']);
        static::assertStringContainsString('type="email"', $form->control($changed));
        $form->close();
    }

    public function testEntityContextUsesCakeValidationMetadata(): void
    {
        $table = new Table(['alias' => 'Articles']);
        $table->setSchema(new TableSchema('articles', ['title' => ['type' => 'string', 'length' => 80]]));
        $table->getValidator()->requirePresence('title')->maxLength('title', 40);
        $entity = new Entity(['title' => 'Bound']);
        $entity->setError('title', ['invalid' => 'Fix it']);

        $form = $this->helper()->bind($entity, ['context' => ['table' => $table]]);
        static::assertSame('Bound', $form->field('title')->state->value);
        static::assertSame(40, $form->field('title')->state->constraints['maxlength']);
        static::assertSame(['Fix it'], $form->field('title')->state->messages);
    }

    public function testContextFirstAndQuerySources(): void
    {
        $helper = $this->helper(['title' => 'posted']);
        $form = $helper->bind(new ArrayContext(['data' => ['title' => 'context']]), ['valueSources' => [
            'context',
            'data',
        ]]);
        static::assertSame('context', $form->field('title')->state->value);
        $helper->getView()->setRequest($helper->getView()->getRequest()->withQueryParams(['q' => 'term']));
        $query = $helper->bind(null, ['valueSources' => ['query']]);
        static::assertSame('term', $query->field('q')->state->value);
    }

    public function testInvalidSourcesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()
            ->bind(null, ['valueSources' => ['cookies']])
            ->field('name');
    }

    public function testInvalidFieldPathsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FieldDefinition::make('../name');
    }

    public function testArrayContextsApplyAssociationAndChoiceConventions(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('tags', ['a' => 'A']);
        $helper->getView()->set('statuses', ['draft' => 'Draft']);
        $form = $helper->bind(new ArrayContext([
            'data' => ['tags' => ['_ids' => ['a']]],
            'errors' => ['tags' => ['invalid' => 'Invalid tags']],
            'required' => ['tags' => true],
        ]));
        $field = $form->field('tags._ids');

        static::assertSame(['a'], $field->state->value);
        static::assertSame(['Invalid tags'], $field->state->messages);
        static::assertTrue($field->state->constraints['multiple']);
        static::assertTrue($field->state->constraints['required']);
        static::assertSame('select', $form->field('status')->state->type);
        static::assertSame(['draft' => 'Draft'], $form->field('status')->state->options['options']);
    }
}
