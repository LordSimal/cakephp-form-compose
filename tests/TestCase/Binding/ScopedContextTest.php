<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Binding;

use Cake\ORM\Entity;
use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\AssociationTestCase;
use FormCompose\Test\Fixture\Authoring\ScopedArticleForm;

/**
 * Verifies prefix delegation against real Cake contexts and ORM associations.
 */
class ScopedContextTest extends AssociationTestCase
{
    public function testComposedOrmFieldsKeepSubmittedValuesConstraintsAndFeedback(): void
    {
        $article = $this->article();
        /** @var list<Entity> $comments */
        $comments = $article->get('comments');
        $comments[0]->setError('body', ['invalid' => '<Invalid comment>']);
        $form = $this->helper(['comments' => [['body' => 'Submitted <comment>']]])->bind(
            $article,
            ['context' => ['table' => $this->articles]],
            blueprint: ScopedArticleForm::class,
        );
        $html = $form->open() . $form->blueprint() . $form->close();
        $xpath = $this->xpath($html);
        static::assertTrue($xpath->evaluate('boolean(//input[@name="comments[0][id]"][@type="hidden"][@value="10"])'));
        static::assertTrue($xpath->evaluate(
            'boolean(//textarea[@name="comments[0][body]"][@required][@maxlength="40"][@aria-invalid="true"])',
        ));
        static::assertStringContainsString('Submitted &lt;comment&gt;', $html);
        static::assertStringContainsString('&lt;Invalid comment&gt;', $html);
        static::assertStringContainsString('for="form-compose-1-comments.0.body"', $html);
        static::assertStringContainsString('id="form-compose-1-comments.0.body-errors"', $html);
        static::assertTrue($xpath->evaluate(
            'boolean(//input[@name="details[summary]"][@maxlength="60"][@value="Summary"])',
        ));
    }

    public function testAssociationMetadataErrorsAndIdentityDelegateToTheOriginalContext(): void
    {
        $article = $this->article();
        /** @var Entity $details */
        $details = $article->get('details');
        $details->setError('summary', ['required' => '<Summary required>']);

        $form = $this->helper()->bind($article, ['context' => ['table' => $this->articles]]);
        $root = $form->binding->context;
        $scoped = new FormContext($root, $form->binding->request)->at('details')->context;
        static::assertSame('Summary', $scoped->val('summary'));
        static::assertSame('string', $scoped->type('summary'));
        static::assertTrue($scoped->isRequired('summary'));
        static::assertSame(60, $scoped->getMaxLength('summary'));
        static::assertSame($root->getRequiredMessage('details.summary'), $scoped->getRequiredMessage('summary'));
        static::assertSame($root->attributes('details.summary'), $scoped->attributes('summary'));
        static::assertTrue($scoped->hasError('summary'));
        static::assertSame(['required' => '<Summary required>'], $scoped->error('summary'));
        static::assertSame($root->getPrimaryKey(), $scoped->getPrimaryKey());
        static::assertSame($root->isPrimaryKey('details.id'), $scoped->isPrimaryKey('id'));
        static::assertFalse($scoped->isCreate());
        static::assertSame(['id', 'summary', 'is_public'], $scoped->fieldNames());
    }

    public function testUnloadedAssociationsStillExposeSchemaAndValidationMetadata(): void
    {
        $form = $this->helper()->bind(new Entity([], ['source' => 'Articles']), ['context' => [
            'table' => $this->articles,
        ]]);
        $context = new FormContext($form->binding->context, $form->binding->request)->at('details')->context;
        static::assertNull($context->val('summary'));
        static::assertSame('string', $context->type('summary'));
        static::assertTrue($context->isRequired('summary'));
        static::assertSame(60, $context->getMaxLength('summary'));
        static::assertSame([], $context->fieldNames());
        static::assertFalse($context->hasError('summary'));
    }

    public function testNestedScopesPreserveExplicitEmptyValuesAndValueOptions(): void
    {
        $helper = $this->helper();
        $root = new ArrayContext([
            'data' => [
                'post' => ['author' => [
                    'null' => null,
                    'false' => false,
                    'zero' => 0,
                    'empty' => '',
                ]],
            ],
        ]);
        $scope = new FormContext($root, $helper->getView()->getRequest())->at('post')->at('author');
        static::assertNull($scope->context->val('null'));
        static::assertFalse($scope->context->val('false'));
        static::assertSame(0, $scope->context->val('zero'));
        static::assertSame('', $scope->context->val('empty'));
        static::assertSame('fallback', $scope->context->val('missing', ['default' => 'fallback']));
        static::assertSame(['null', 'false', 'zero', 'empty'], $scope->context->fieldNames());
        static::assertSame($helper->getView()->getRequest(), $scope->request);
    }
}
