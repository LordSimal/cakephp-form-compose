<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Binding;

use FormCompose\Test\Fixture\Enum\UnbackedStatus;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

/** Rejects malformed fluent settings before successful input registration. */
class FieldConfigurationTest extends FormTestCase
{
    /**
     * @param list<mixed> $arguments Deliberately invalid public method arguments.
     * @param class-string<\Throwable> $exception Expected failure contract.
     */
    #[DataProvider('invalidSettings')]
    public function testInvalidSettingsLeaveTheInputAvailable(string $method, array $arguments, string $exception): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $this->expectException($exception);
        try {
            $field = $form->control('title');
            $this->invokeBuilder($field, $method, ...$arguments);
            $field->render();
        } finally {
            static::assertStringContainsString('name="title"', $form->control('title')->render());
            $form->close();
        }
    }

    /** @return array<string, array{string, list<mixed>, class-string<\Throwable>}> */
    public static function invalidSettings(): array
    {
        return [
            'attributes scalar' => ['attributes', ['wide'], TypeError::class],
            'attributes null' => ['attributes', [null], TypeError::class],
            'attribute keys' => ['attributes', [['wide']], InvalidArgumentException::class],
            'part scalar' => ['partAttributes', ['help', 'hint'], TypeError::class],
            'nested keys' => ['partAttributes', ['help', ['hint']], InvalidArgumentException::class],
            'empty attribute part' => ['partAttributes', ['', []], InvalidArgumentException::class],
            'templates scalar' => ['replaceTemplates', ['forms/title'], TypeError::class],
            'empty template part' => ['partTemplate', ['', 'forms/title'], InvalidArgumentException::class],
            'empty element' => ['template', [''], InvalidArgumentException::class],
            'nonstring element' => ['template', [false], TypeError::class],
            'pure enum class' => ['choices', [UnbackedStatus::class], InvalidArgumentException::class],
            'ordinary class' => ['choices', [TypeError::class], InvalidArgumentException::class],
            'unknown enum' => ['choices', ['UnknownChoiceEnum'], InvalidArgumentException::class],
            'choice scalar' => ['choices', [42], TypeError::class],
        ];
    }

    public function testExtensionDataAndIntegrationAttributesRemainOpen(): void
    {
        $marker = new InvalidArgumentException();
        $field = $this->helper()
            ->form()
            ->bind()
            ->field('title')
            ->attributes(['x-model' => 'article.title', 'data-feature' => 'editor', 'class' => ['wide' => true]])
            ->partAttributes('custom-part', ['data-application' => 'yes'])
            ->partTemplate('custom-part', 'forms/custom')
            ->extensionData(['customConfiguration' => $marker])
            ->resolve();
        static::assertStringContainsString('x-model="article.title"', (string)$field->attributes());
        static::assertSame('yes', $field->attributes('custom-part')->values['data-application']);
        static::assertSame($marker, $field->state->config->extensionData['customConfiguration']);
    }
}
