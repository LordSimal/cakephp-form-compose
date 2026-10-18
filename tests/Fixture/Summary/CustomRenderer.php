<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Summary;

use FormCompose\Configuration\SummaryConfig;
use FormCompose\Rendering\ErrorSummaryRenderer;

/**
 * Specializes preparation through the normal summary renderer dependency.
 */
class CustomRenderer extends ErrorSummaryRenderer
{
    /**
     * @return array{\FormCompose\Rendering\Attributes, \FormCompose\Rendering\Attributes}
     */
    protected function attributes(string $bindingId, SummaryConfig $config): array
    {
        [$attributes, $title] = parent::attributes($bindingId, $config);

        return [$attributes->merge(['class' => 'custom-summary']), $title];
    }
}
