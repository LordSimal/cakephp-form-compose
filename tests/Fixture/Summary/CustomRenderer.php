<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Summary;

use FormCompose\Rendering\ErrorSummaryRenderer;

/**
 * Specializes preparation through the normal summary renderer dependency.
 */
class CustomRenderer extends ErrorSummaryRenderer
{
    /**
     * @param array<string, mixed> $options Summary presentation.
     * @return array{\FormCompose\Rendering\Attributes, \FormCompose\Rendering\Attributes}
     */
    protected function attributes(string $bindingId, array $options): array
    {
        [$attributes, $title] = parent::attributes($bindingId, $options);

        return [$attributes->merge(['class' => 'custom-summary']), $title];
    }
}
