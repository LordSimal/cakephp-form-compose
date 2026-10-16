<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\RepeatedCompiler;
use FormCompose\FormContext;

/**
 * Selects application row keys while retaining the shared validation boundary.
 */
class CustomRepeatedCompiler extends RepeatedCompiler
{
    /**
     * Use request-selected keys or the ordinary source-aware inference.
     *
     * @return list<int|string>
     */
    protected function keys(string $path, FormContext $context): array
    {
        /** @var list<int|string>|null $keys */
        $keys = $context->request->getAttribute('rowKeys');

        return $keys ?? parent::keys($path, $context);
    }
}
