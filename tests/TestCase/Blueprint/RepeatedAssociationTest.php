<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\Form\FormProtector;
use Cake\ORM\Entity;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Test\AssociationTestCase;
use FormCompose\Test\Fixture\Authoring\CommentsWithIdForm;
use FormCompose\Test\Fixture\Repeat\CommentForm;
use RuntimeException;

/**
 * Exercises actual Cake association contexts, errors, and locked nested primary keys.
 */
class RepeatedAssociationTest extends AssociationTestCase
{
    public function testRepeatedEntitiesRetainMetadataAndSeparateDirectAndNestedErrors(): void
    {
        $article = $this->article();
        /** @var list<Entity> $comments */
        $comments = $article->get('comments');
        $comments[0]->setError('body', ['required' => 'Comment required']);
        $article->setError('comments', ['count' => 'Add another comment']);
        $form = $this->helper()
            ->form($article)
            ->context(['table' => $this->articles])
            ->blueprint(CommentsWithIdForm::class)
            ->bind();
        $html = $form->render();
        static::assertStringContainsString('type="hidden"', $html);
        static::assertStringContainsString('<textarea', $html);
        static::assertStringContainsString('maxlength="40"', $html);
        static::assertSame(1, substr_count($html, 'Comment required'));
        static::assertSame(1, substr_count($html, 'Add another comment'));
    }

    public function testAllRowsShareOneProtectorAndFailedWrappersContributeNothing(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $article = $this->article();
        /** @var list<Entity> $comments */
        $comments = $article->get('comments');
        $article->set('comments', [
            0 => $comments[0],
            2 => new Entity(['id' => 12, 'body' => 'Third'], ['source' => 'Comments']),
        ]);
        $form = $helper->form($article)
            ->url('/articles/save')
            ->method('post')
            ->context(['table' => $this->articles])
            ->bind();
        $html = $form->open();
        try {
            $form->embedMany(path: 'comments', blueprint: CommentForm::class)
                ->partTemplate('control', 'repeat/failure')
                ->render();
            static::fail('Expected failure after row registration.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Repeated wrapper failed.', $runtimeException->getMessage());
        }

        $html .=
            $form->embedMany(path: 'comments', blueprint: CommentForm::class)->keys([2])->render() . $form->close();
        $tokens = [];
        /** @var NodeList<Element>|false $nodes */
        $nodes = $this->xpath($html)->query('//input[starts-with(@name, "_Token[")]');
        static::assertInstanceOf(NodeList::class, $nodes);
        foreach ($nodes as $node) {
            $name = $node->getAttribute('name');
            $value = $node->getAttribute('value');
            static::assertNotNull($name);
            static::assertNotNull($value);
            $tokens[substr($name, 7, -1)] = $value;
        }

        $data = ['comments' => [2 => ['id' => '12', 'body' => 'Updated']], '_Token' => $tokens];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        $data['comments'][2]['id'] = '99';
        static::assertFalse(new FormProtector()->validate($data, '/articles/save', $session));
        $data['comments'][0] = ['id' => '10', 'body' => 'Injected row'];
        static::assertFalse(new FormProtector()->validate($data, '/articles/save', $session));
    }

    public function testClearedAssociationsDoNotExposeLoadedChildErrorsAsCollectionFeedback(): void
    {
        $article = $this->article();
        /** @var list<Entity> $comments */
        $comments = $article->get('comments');
        $comments[0]->setError('body', ['required' => 'Stale child error']);
        $article->setError('comments', ['count' => 'Collection needs a comment']);
        $form = $this->helper(['comments' => []])
            ->form($article)
            ->context(['table' => $this->articles])
            ->bind();
        $form->open();

        $html = $form->embedMany(path: 'comments', blueprint: CommentForm::class)->render();
        static::assertStringContainsString('Collection needs a comment', $html);
        static::assertStringNotContainsString('Stale child error', $html);
        static::assertSame(0, $this->xpath($html)->query('//input | //textarea')->length);
        $form->close();
    }
}
