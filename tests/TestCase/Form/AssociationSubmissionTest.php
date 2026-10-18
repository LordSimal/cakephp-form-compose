<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Form\FormProtector;
use Cake\ORM\Entity;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Test\AssociationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks association payloads, empty selections, ORM marshalling, and tamper protection.
 */
class AssociationSubmissionTest extends AssociationTestCase
{
    /**
     * Distinguish a submitted empty or null selection from absent request data.
     *
     * @return array<string, array{mixed, int}>
     */
    public static function submittedSelections(): array
    {
        return [
            'changed' => [['css'], 1],
            'empty array' => [[], 0],
            'hidden fallback' => ['', 0],
            'null' => [null, 0],
            'zero identifier' => [['0'], 1],
        ];
    }

    #[DataProvider('submittedSelections')]
    public function testSubmittedSelectionsOverrideLoadedAssociations(mixed $submitted, int $selected): void
    {
        $helper = $this->helper(['tags' => ['_ids' => $submitted]]);
        $helper->getView()->set('tags', ['php' => 'PHP', 'cake' => 'CakePHP', 'css' => 'CSS', 0 => 'Zero']);
        $form = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->bind();
        static::assertSame($submitted, $form->field('tags._ids')->resolve()->state->value);
        $form->open();
        $html = $form->control('tags._ids')->render();
        $form->close();

        static::assertSame((float)$selected, $this->xpath($html)->evaluate('count(//option[@selected])'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//option[@value="php"][@selected])'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//option[@value="cake"][@selected])'));
    }

    public function testExplicitAndConfiguredValueSourcesRetainTheirPrecedence(): void
    {
        $helper = $this->helper(['tags' => ['_ids' => ['css']]]);
        $context = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->valueSources(['context', 'data'])
            ->bind();
        static::assertSame(['php', 'cake'], $context->field('tags._ids')->resolve()->state->value);
        static::assertSame(
            [],
            $context->field('tags._ids')->value([])->resolve()->state->value,
        );
        $helper->getView()->setRequest($helper->getView()
            ->getRequest()
            ->withQueryParams(['tags' => ['_ids' => ['php']]]));
        $query = $helper->form($this->article())
            ->context(['table' => $this->articles])
            ->valueSources(['query', 'context'])
            ->bind();
        static::assertSame(['php'], $query->field('tags._ids')->resolve()->state->value);
    }

    public function testAssociationFormsProtectNestedFieldsAndLockPrimaryKeys(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $helper->getView()->set('tags', ['php' => 'PHP', 'css' => 'CSS']);
        $form = $helper->form($this->article())
            ->url('/articles/save')
            ->context(['table' => $this->articles])
            ->bind();
        $form->field('comments.0.author_id')->resolve()->attributes();
        $html = $form->open();
        $html .= $form->control('id')->render() . $form->control('tags._ids')->render();
        $html .=
            $form->control('details.summary')->render()
            . $form->control('comments.0.id')->render()
            . $form->control('comments.0.body')->render();
        $html .= $form->control('tags.0._joinData.priority')->render() . $form->close();
        $data = [
            'id' => '42',
            'tags' => ['_ids' => ['css'], 0 => ['_joinData' => ['priority' => '4']]],
            'details' => ['summary' => 'Changed'],
            'comments' => [['id' => '10', 'body' => 'Changed']],
            '_Token' => $this->tokens($html),
        ];
        $protector = new FormProtector();
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue($protector->validate($data, '/articles/save', $session));
        $data['tags']['_ids'] = '';
        static::assertTrue($protector->validate($data, '/articles/save', $session));
        $data['comments'][0]['id'] = '99';
        static::assertFalse($protector->validate($data, '/articles/save', $session));
        $data['comments'][0]['id'] = '10';
        $data['comments'][0]['author_id'] = '1';
        static::assertFalse($protector->validate($data, '/articles/save', $session));
    }

    public function testDisabledAssociationInputsDoNotBecomeProtectedSubmissions(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->form($this->article())
            ->url('/articles/save')
            ->context(['table' => $this->articles])
            ->bind();
        $html = $form->open() . $form->control('id')->render();
        $html .=
            $form->control('tags._ids')->disabled(true)->render()
            . $form->close();
        $data = ['id' => '42', '_Token' => $this->tokens($html)];
        $protector = new FormProtector();
        $session = $helper->getView()->getRequest()->getSession()->id();

        static::assertTrue($protector->validate($data, '/articles/save', $session));
        $data['tags'] = ['_ids' => ['php']];
        static::assertFalse($protector->validate($data, '/articles/save', $session));
    }

    public function testBrowserPayloadsPatchAssociationsAndCanClearSelections(): void
    {
        $connection = $this->tags->getConnection();
        $connection->execute('CREATE TABLE tags (tag_key VARCHAR(20) PRIMARY KEY, name VARCHAR(100))');
        try {
            $connection->execute(
                "INSERT INTO tags (tag_key, name) VALUES ('php', 'PHP'), ('cake', 'CakePHP'), ('css', 'CSS')",
            );
            $this->tags->setDisplayField('name');
            $helper = $this->helper();
            $helper->getView()->set('tags', $this->tags->find('list'));
            $form = $helper->form($this->article())
                ->context(['table' => $this->articles])
                ->bind();
            $html = $form->open() . $form->control('tags._ids')->render() . $form->close();
            static::assertSame(3.0, $this->xpath($html)->evaluate('count(//option)'));
            static::assertSame(2.0, $this->xpath($html)->evaluate('count(//option[@selected])'));
            static::assertStringContainsString('CakePHP</option>', $html);
            $article = $this->articles->patchEntity(
                $this->article(),
                [
                    'tags' => ['_ids' => ['css']],
                    'details' => ['summary' => 'Updated summary'],
                    'comments' => [['id' => 10, 'body' => 'Updated comment']],
                ],
                ['associated' => ['Tags', 'ArticleDetails', 'Comments'], 'validate' => false],
            );
            /** @var list<Entity> $tags */
            $tags = $article->get('tags');
            static::assertSame(['css'], array_map(static fn(Entity $tag): mixed => $tag->get('tag_key'), $tags));
            /** @var Entity $details */
            $details = $article->get('details');
            /** @var list<Entity> $comments */
            $comments = $article->get('comments');
            static::assertSame('Updated summary', $details->get('summary'));
            static::assertSame('Updated comment', $comments[0]->get('body'));
            $cleared = $this->articles->patchEntity(
                $article,
                ['tags' => ['_ids' => '']],
                [
                    'associated' => ['Tags'],
                    'validate' => false,
                ],
            );
            static::assertSame([], $cleared->get('tags'));
        } finally {
            $connection->execute('DROP TABLE tags');
        }
    }

    /**
     * Extract CakePHP's token payload from the actual rendered hidden inputs.
     *
     * @return array<string, string>
     */
    protected function tokens(string $html): array
    {
        /** @var NodeList<Element>|false $nodes */
        $nodes = $this->xpath($html)->query('//input[starts-with(@name, "_Token[")]');
        static::assertInstanceOf(NodeList::class, $nodes);
        $tokens = [];
        foreach ($nodes as $node) {
            static::assertInstanceOf(Element::class, $node);
            $name = $node->getAttribute('name');
            $value = $node->getAttribute('value');
            static::assertNotNull($name);
            static::assertNotNull($value);
            $tokens[substr($name, 7, -1)] = $value;
        }

        return $tokens;
    }
}
