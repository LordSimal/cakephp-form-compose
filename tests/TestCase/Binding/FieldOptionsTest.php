<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Binding;

use FormCompose\Test\Fixture\Enum\PublicationStatus;
use FormCompose\Test\Fixture\Enum\UnbackedStatus;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * Verifies malformed core options fail during public binding without closing extension bags.
 */
class FieldOptionsTest extends FormTestCase
{
    /**
     * @param array<string, mixed> $options Invalid field configuration.
     */
    #[DataProvider('invalidBags')]
    public function testMalformedCoreOptionsFailBeforeRendering(array $options, string $message): void
    {
        $form = $this->helper()->bind();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $form->field('title', $options);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidBags(): array
    {
        return [
            'attributes scalar' => [['attributes' => 'wide'], 'attributes option must be an array'],
            'attributes null' => [['attributes' => null], 'attributes option must be an array'],
            'attribute keys' => [['attributes' => ['wide']], 'attributes option must use nonempty string keys'],
            'parts scalar' => [['partAttributes' => false], 'partAttributes option must be an array'],
            'part scalar' => [['partAttributes' => ['help' => 'hint']], 'Each partAttributes entry'],
            'nested keys' => [
                ['partAttributes' => ['help' => ['hint']]],
                'attributes option must use nonempty string keys',
            ],
            'templates scalar' => [['templates' => 'forms/title'], 'templates option must be an array'],
            'empty part name' => [
                ['templates' => ['' => 'forms/title']],
                'templates option must use nonempty string keys',
            ],
            'empty element' => [['templates' => ['control' => '']], 'Each templates entry'],
            'nonstring element' => [['templates' => ['control' => false]], 'Each templates entry'],
            'pure enum class' => [['options' => UnbackedStatus::class], 'backed enum class name'],
            'ordinary class' => [['options' => stdClass::class], 'backed enum class name'],
            'unknown class' => [['options' => 'UnknownChoiceEnum'], 'backed enum class name'],
            'single enum case' => [['options' => PublicationStatus::Published], 'backed enum class name'],
            'choices scalar' => [['options' => 'draft'], 'options option must be iterable'],
        ];
    }

    public function testExtensionOptionsAndIntegrationAttributesRemainOpen(): void
    {
        $form = $this->helper()->bind();
        $field = $form->field('title', [
            'customConfiguration' => new InvalidArgumentException(),
            'attributes' => ['x-model' => 'article.title', 'data-feature' => 'editor', 'class' => ['wide' => true]],
            'partAttributes' => ['custom-part' => ['data-application' => 'yes']],
            'templates' => ['custom-part' => 'forms/custom'],
        ]);
        static::assertStringContainsString('x-model="article.title"', (string)$field->attributes());
        static::assertSame('yes', $field->attributes('custom-part')->values['data-application']);
    }
}
