<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Binding;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\View\Form\ArrayContext;
use FormCompose\Configuration\FieldConfig;
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
        ])
            ->form(new ArrayContext(['data' => ['nil' => 'original']]))
            ->bind();
        static::assertNull($form->field('nil')->resolve()->state->value);
        static::assertSame(0, $form->field('zero')->resolve()->state->value);
        static::assertFalse($form->field('off')->resolve()->state->value);
        static::assertSame('', $form->field('blank')->resolve()->state->value);
        static::assertNull(
            $form->field('missing')
                ->value(null)
                ->default('fallback')
                ->resolve()
                ->state
                ->value,
        );
        static::assertSame(
            'fallback',
            $form->field('missing')
                ->default('fallback')
                ->resolve()
                ->state
                ->value,
        );
    }

    public function testContextInferenceConstraintsAndNestedNames(): void
    {
        $form = $this->helper()
            ->form([
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
            ])
            ->bind();
        $title = $form->field('title')->resolve()->state;
        static::assertSame('Hello', $title->value);
        static::assertSame(['Invalid'], $title->messages);
        static::assertSame(['required' => true, 'maxlength' => 30], $title->constraints);
        static::assertSame('textarea', $form->field('body')->resolve()->state->type);
        static::assertSame('checkbox', $form->field('active')->resolve()->state->type);
        static::assertSame('any', $form->field('price')->resolve()->state->constraints['step']);
        static::assertSame('1', $form->field('count')->resolve()->state->constraints['step']);
        static::assertSame('comments[0][body]', $form->field('comments.0.body')->resolve()->state->name);
        static::assertFalse(
            $form->field('title')
                ->disabled(true)
                ->resolve()
                ->state
                ->constraints['required'],
        );
    }

    public function testInlineOptionsCanBeReusedAndExplicitOverridesWin(): void
    {
        $options = FieldConfig::builder()->type('email')->label('Email')->help('Work email')->config();
        $changed = $options->merge(FieldConfig::builder()->label('Contact')->config());
        $form = $this->helper()->form()->url('/articles/save')->bind();
        static::assertSame('Contact', $form->field('email')->apply($changed)->resolve()->state->config->label);
        static::assertSame(
            'Override',
            $form->field('email')
                ->apply($changed->merge(FieldConfig::builder()->label('Override')->config()))
                ->resolve()
                ->state
                ->config
                ->label,
        );
        static::assertSame('Email', $options->label);
        $form->open();
        static::assertStringContainsString('type="email"', $form->control('email')->apply($changed)->render());
        $form->close();
    }

    public function testEntityContextUsesCakeValidationMetadata(): void
    {
        $table = new Table(['alias' => 'Articles']);
        $table->setSchema(new TableSchema('articles', ['title' => ['type' => 'string', 'length' => 80]]));
        $table->getValidator()->requirePresence('title')->maxLength('title', 40);
        $entity = new Entity(['title' => 'Bound']);
        $entity->setError('title', ['invalid' => 'Fix it']);

        $form = $this->helper()
            ->form($entity)
            ->context(['table' => $table])
            ->bind();
        static::assertSame('Bound', $form->field('title')->resolve()->state->value);
        static::assertSame(40, $form->field('title')->resolve()->state->constraints['maxlength']);
        static::assertSame(['Fix it'], $form->field('title')->resolve()->state->messages);
    }

    public function testContextFirstAndQuerySources(): void
    {
        $helper = $this->helper(['title' => 'posted']);
        $form = $helper->form(new ArrayContext(['data' => ['title' => 'context']]))
            ->valueSources([
                'context',
                'data',
            ])
            ->bind();
        static::assertSame('context', $form->field('title')->resolve()->state->value);
        $helper->getView()->setRequest($helper->getView()->getRequest()->withQueryParams(['q' => 'term']));
        $query = $helper->form()
            ->valueSources(['query'])
            ->bind();
        static::assertSame('term', $query->field('q')->resolve()->state->value);
    }

    public function testInvalidSourcesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()
            ->form()
            ->valueSources(['cookies'])
            ->bind()
            ->field('name')
            ->resolve();
    }

    public function testInvalidFieldPathsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->form()->bind()->field('../name')->resolve();
    }

    public function testArrayContextsApplyAssociationAndChoiceConventions(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('tags', ['a' => 'A']);
        $helper->getView()->set('statuses', ['draft' => 'Draft']);
        $form = $helper->form(new ArrayContext([
            'data' => ['tags' => ['_ids' => ['a']]],
            'errors' => ['tags' => ['invalid' => 'Invalid tags']],
            'required' => ['tags' => true],
        ]))
            ->bind();
        $field = $form->field('tags._ids')->resolve();

        static::assertSame(['a'], $field->state->value);
        static::assertSame(['Invalid tags'], $field->state->messages);
        static::assertTrue($field->state->constraints['multiple']);
        static::assertTrue($field->state->constraints['required']);
        static::assertSame('select', $form->field('status')->resolve()->state->type);
        static::assertSame(['draft' => 'Draft'], $form->field('status')->resolve()->state->config->choices);
    }
}
