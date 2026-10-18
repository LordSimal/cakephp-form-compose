<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use FormCompose\Test\FormTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use TypeError;

/** Rejects invalid template data at authoring time before rendering side effects. */
class TemplateDataValidationTest extends FormTestCase
{
    /** @return iterable<string, array{string, mixed}> */
    public static function invalidData(): iterable
    {
        foreach (['form', 'field', 'button'] as $scope) {
            foreach ([
                'null' => null,
                'boolean' => false,
                'string' => 'data',
                'number' => 0,
                'object' => new stdClass(),
            ] as $name => $data) {
                yield $scope . ' ' . $name => [$scope, $data];
            }
        }
    }

    #[DataProvider('invalidData')]
    public function testInvalidContainersLeaveBuildersUsable(string $scope, mixed $data): void
    {
        $formBuilder = $this->helper()->form();
        $form = $formBuilder->bind();
        $builder = match ($scope) {
            'form' => $formBuilder,
            'field' => $form->control('email'),
            default => $form->submit('Save'),
        };
        $this->expectException(TypeError::class);
        try {
            $this->invokeBuilder($builder, 'templateData', $data);
        } finally {
            static::assertSame([], $builder->config()->presentation->templateData);
            $form->open();
            static::assertStringContainsString('name="email"', $form->control('email')->render());
            static::assertStringContainsString('<button', $form->submit('Save')->render());
            $form->close();
        }
    }
}
