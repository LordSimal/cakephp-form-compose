<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use Cake\Database\Schema\TableSchema;
use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\View\Form\ArrayContext;
use FormCompose\Binding\FormBinding;
use FormCompose\FormSession;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Enum\DiscoveredForm;
use FormCompose\Test\Fixture\Enum\Priority;
use FormCompose\Test\Fixture\Enum\PublicationStatus;
use FormCompose\Test\FormTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Exercises schema enum discovery through helper, ORM, and scoped blueprint entry points.
 */
class EnumDiscoveryTest extends FormTestCase
{
    /** @var array<string, class-string<\Cake\Database\TypeInterface>> */
    protected array $typeMap;

    /** @var array<string, \Cake\Database\TypeInterface> */
    protected array $types;

    protected function setUp(): void
    {
        parent::setUp();
        $this->typeMap = TypeFactory::getMap();
        $this->types = TypeFactory::buildAll();
    }

    protected function tearDown(): void
    {
        TypeFactory::setMap($this->typeMap);
        foreach ($this->types as $name => $type) {
            TypeFactory::set($name, $type);
        }

        parent::tearDown();
    }

    public function testSchemaEnumsTakePrecedenceOverConventionalViewVariables(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('statuses', ['unexpected' => 'Unexpected choice']);
        $form = $helper->bind(new ArrayContext([
            'schema' => ['status' => ['type' => EnumType::from(PublicationStatus::class)]],
            'data' => ['status' => PublicationStatus::Published],
        ]));
        $field = $form->field('status');
        static::assertSame('select', $field->state->type);
        static::assertSame(PublicationStatus::class, $field->state->options['options']);
        $form->open();
        $html = $field->control();
        static::assertSame('published', $this->xpath($html)->evaluate('string(//option[@selected]/@value)'));
        static::assertStringContainsString('In Review', $html);
        static::assertStringNotContainsString('Unexpected choice', $html);
        $form->close();
    }

    /**
     * @param array<string, mixed> $options Explicit field overrides.
     * @param mixed $expectedChoices Expected retained choices, including null.
     */
    #[DataProvider('explicitOverrides')]
    public function testExplicitOptionsAndTypesRemainAuthoritative(
        array $options,
        string $expectedType,
        mixed $expectedChoices,
    ): void {
        $form = $this->helper()->bind(new ArrayContext([
            'schema' => ['status' => ['type' => EnumType::from(PublicationStatus::class)]],
        ]));
        $field = $form->field('status', $options);
        static::assertSame($expectedType, $field->state->type);
        static::assertSame($expectedChoices, $field->state->options['options'] ?? null);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, mixed}>
     */
    public static function explicitOverrides(): array
    {
        return [
            'subset' => [['options' => ['published' => 'Available']], 'select', ['published' => 'Available']],
            'empty choices' => [['options' => []], 'select', []],
            'explicit null opts out' => [['options' => null], 'text', null],
            'text' => [['type' => 'text'], 'text', null],
            'hidden' => [['type' => 'hidden'], 'hidden', null],
            'custom type' => [['type' => 'application-status'], 'application-status', null],
            'radio' => [['type' => 'radio'], 'radio', PublicationStatus::class],
            'checkbox group' => [['type' => 'multicheckbox'], 'multicheckbox', PublicationStatus::class],
        ];
    }

    public function testSubmittedZeroAndIntegerEnumLabelsWorkWithDiscoveredRadios(): void
    {
        $form = $this->helper(['priority' => '0'])->bind(new ArrayContext([
            'schema' => ['priority' => ['type' => EnumType::from(Priority::class)]],
            'data' => ['priority' => Priority::Urgent],
        ]));
        $form->open();

        $html = $form->control('priority', ['type' => 'radio']);
        static::assertSame('0', $this->xpath($html)->evaluate('string(//input[@checked]/@value)'));
        static::assertStringContainsString('Standard &lt;priority&gt;', $html);
        static::assertStringNotContainsString('<priority>', $html);
        $form->close();
    }

    public function testEntityAndScopedBlueprintPathsUseTheEnumSchema(): void
    {
        $type = EnumType::from(PublicationStatus::class);
        $table = new Table(['alias' => 'Articles']);
        $table->setSchema(new TableSchema('articles', ['status' => ['type' => $type]]));

        $comments = new Table(['alias' => 'Comments']);
        $comments->setSchema(new TableSchema('comments', ['status' => ['type' => $type]]));

        $table->hasMany('Comments', ['targetTable' => $comments]);
        $entity = new Entity([
            'status' => PublicationStatus::Published,
            'comments' => [new Entity(['status' => PublicationStatus::InReview])],
        ]);
        $form = $this->helper()->bind($entity, ['context' => ['table' => $table]]);
        static::assertSame('select', $form->field('status')->state->type);
        $form->open();
        $html = $form->definition(DiscoveredForm::class, at: 'comments.0');
        static::assertSame('comments[0][status]', $this->xpath($html)->evaluate('string(//select/@name)'));
        static::assertSame('in-review', $this->xpath($html)->evaluate('string(//option[@selected]/@value)'));
        $form->close();
    }

    public function testUnregisteredEnumLikeTypesRetainConventionalFallbacks(): void
    {
        $helper = $this->helper();
        $context = new ArrayContext(['schema' => ['status' => ['type' => 'enum-unregistered']]]);
        static::assertSame('text', $helper->bind($context)->field('status')->state->type);
        $helper->getView()->set('statuses', ['a' => 'A']);
        static::assertSame(['a' => 'A'], $helper->bind($context)->field('status')->state->options['options']);
    }

    public function testDiscoveryDoesNotRequireViewVariableLookupOrRendering(): void
    {
        $view = $this->helper()->getView();
        $theme = Theme::base();
        $form = new FormSession(
            new FormBinding(
                new ArrayContext([
                    'schema' => ['status' => ['type' => EnumType::from(PublicationStatus::class)]],
                ]),
                $view->getRequest(),
                'isolated',
                [],
            ),
            new RenderContext(new PhpRenderer($view, $theme), $theme),
        );
        $field = $form->field('status');
        static::assertSame(PublicationStatus::class, $field->state->options['options']);
        static::assertSame('select', $field->state->type);
        $html = $form->open() . $form->close();
        static::assertStringNotContainsString('name="status"', $html);
    }
}
