<?php
declare(strict_types=1);

namespace FormCompose\Test;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use DOMDocument;
use DOMXPath;

/**
 * Builds real ORM associations and entity graphs without requiring stored records.
 *
 * Tags use a string primary key. Details use a custom hasOne property; comments
 * have their own belongsTo and belongsToMany associations. The junction exposes
 * editable priority data through CakePHP's normal _joinData property.
 */
abstract class AssociationTestCase extends FormTestCase
{
    protected Table $articles;

    protected Table $tags;

    protected Table $comments;

    protected Table $details;

    /**
     * Prepare explicit schemas and validators so context resolution uses real ORM metadata.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->articles = $this->table('Articles', [
            'id' => ['type' => 'integer'],
            'author_id' => ['type' => 'integer'],
            'title' => ['type' => 'string', 'length' => 80],
        ]);
        $authors = $this->table('Authors', [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string', 'length' => 60],
        ]);
        $this->tags = $this->table(
            'Tags',
            [
                'tag_key' => ['type' => 'string', 'length' => 20],
                'name' => ['type' => 'string'],
            ],
            'tag_key',
        );
        $this->comments = $this->table('Comments', [
            'id' => ['type' => 'integer'],
            'article_id' => ['type' => 'integer'],
            'author_id' => ['type' => 'integer'],
            'body' => ['type' => 'text'],
        ]);
        $this->details = $this->table('ArticleDetails', [
            'id' => ['type' => 'integer'],
            'article_id' => ['type' => 'integer'],
            'summary' => ['type' => 'string', 'length' => 100],
            'is_public' => ['type' => 'boolean'],
        ]);
        $junction = $this->table('ArticlesTags', [
            'id' => ['type' => 'integer'],
            'article_id' => ['type' => 'integer'],
            'tag_key' => ['type' => 'string'],
            'priority' => ['type' => 'integer'],
        ]);
        $commentJunction = $this->table('CommentsTags', [
            'id' => ['type' => 'integer'],
            'comment_id' => ['type' => 'integer'],
            'tag_key' => ['type' => 'string'],
        ]);
        $this->articles->belongsTo('Authors', ['targetTable' => $authors]);
        $this->articles->hasOne('ArticleDetails', ['targetTable' => $this->details, 'propertyName' => 'details']);
        $this->articles->hasMany('Comments', ['targetTable' => $this->comments]);
        $this->articles->belongsToMany('Tags', [
            'targetTable' => $this->tags,
            'targetForeignKey' => 'tag_key',
            'through' => $junction,
        ]);
        $this->articles->belongsToMany('Topics', [
            'targetTable' => $this->tags,
            'propertyName' => 'topic_tags',
            'targetForeignKey' => 'tag_key',
            'through' => $junction,
        ]);
        $this->comments->belongsTo('Authors', ['targetTable' => $authors]);
        $this->comments->belongsToMany('Tags', [
            'targetTable' => $this->tags,
            'targetForeignKey' => 'tag_key',
            'through' => $commentJunction,
        ]);
        $this->comments->getValidator()->notEmptyString('body')->maxLength('body', 40);
        $this->details->getValidator()->notEmptyString('summary')->maxLength('summary', 60);
    }

    /**
     * Build a generic table whose metadata never needs database schema introspection.
     *
     * @param array<string, array<string, mixed>> $columns Column metadata.
     */
    protected function table(string $alias, array $columns, string $primaryKey = 'id'): Table
    {
        $table = new Table(['alias' => $alias]);
        $table->setSchema(new TableSchema($table->getTable(), $columns));
        $table->setPrimaryKey($primaryKey);

        return $table;
    }

    /**
     * Create an existing article with loaded singular, collection, and junction data.
     */
    protected function article(): Entity
    {
        $tags = [
            new Entity(['tag_key' => 'php', 'name' => 'PHP', '_joinData' => new Entity(['priority' => 3])]),
            new Entity(['tag_key' => 'cake', 'name' => 'CakePHP']),
        ];

        return new Entity([
            'id' => 42,
            'author_id' => 2,
            'title' => 'Article',
            'author' => new Entity(['id' => 2, 'name' => 'Ada']),
            'details' => new Entity(['id' => 9, 'summary' => 'Summary', 'is_public' => true]),
            'comments' => [new Entity(['id' => 10, 'body' => 'Comment', 'author_id' => 2, 'tags' => $tags])],
            'tags' => $tags,
            'topic_tags' => $tags,
        ], ['source' => 'Articles', 'markNew' => false]);
    }

    /**
     * Parse rendered markup for selection, naming, and accessibility assertions.
     */
    protected function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $document->loadHTML($html);

        return new DOMXPath($document);
    }
}
