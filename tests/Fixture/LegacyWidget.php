<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use Cake\View\Form\ContextInterface;
use Cake\View\Widget\WidgetInterface;

/**
 * Existing Cake widget used to verify adapter compatibility.
 */
final class LegacyWidget implements WidgetInterface
{
    /**
     * Render a minimal two-name widget without helper inheritance.
     *
     * @param array<string, mixed> $data Widget inputs.
     */
    public function render(array $data, ContextInterface $context): string
    {
        return '<span>legacy widget</span>';
    }

    /**
     * Preserve the widget's existing submitted-field description.
     *
     * @param array<string, mixed> $data Widget inputs.
     * @return list<string>
     */
    public function secureFields(array $data): array
    {
        return [(string)$data['name'] . '[left]', (string)$data['name'] . '[right]'];
    }
}
