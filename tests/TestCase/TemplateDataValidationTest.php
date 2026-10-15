<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * Rejects invalid metadata containers and verifies safe retries before registration.
 */
class TemplateDataValidationTest extends FormTestCase
{
    /**
     * Exercise each public rendering scope with invalid metadata container types.
     *
     * @return iterable<string, array{string, mixed}>
     */
    public static function invalidData(): iterable
    {
        foreach (['form', 'field', 'button'] as $scope) {
            /** @var mixed $data */
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
    public function testInvalidContainersAreRejected(string $scope, mixed $data): void
    {
        $form = $this->helper()->bind();
        if ($scope !== 'form') {
            $form->open();
        }
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The templateData option must be an array.');
        match ($scope) {
            'form' => $form->open(['templateData' => $data]),
            'field' => $form->control('email', ['templateData' => $data]),
            default => $form->submit('Save', ['templateData' => $data]),
        };
    }

    public function testInvalidFieldDataDoesNotRegisterAnInput(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->control('email', ['templateData' => null]);
            static::fail('Expected invalid template metadata to fail.');
        } catch (InvalidArgumentException $exception) {
            static::assertSame('The templateData option must be an array.', $exception->getMessage());
        }
        static::assertStringContainsString('name="email"', $form->control('email'));
        $form->close();
    }

    public function testInvalidOpeningDataLeavesTheFormAvailableForRetry(): void
    {
        $form = $this->helper()->bind(null, ['templateData' => null]);
        try {
            $form->open();
            static::fail('Expected invalid template metadata to fail.');
        } catch (InvalidArgumentException $exception) {
            static::assertSame('The templateData option must be an array.', $exception->getMessage());
        }
        static::assertStringContainsString('<form', $form->open(['templateData' => []]));
        $form->close();
    }
}
