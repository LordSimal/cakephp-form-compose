<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use InvalidArgumentException;

/**
 * Builds and validates relative template candidates for PhpRenderer.
 *
 * Candidates are ordered by variant, type, then generic part. Field candidates are
 * relative to the theme layer directly; form and button candidates include their
 * scope prefix. For example, a primary submit button tries
 * button/variants/primary/control before button/submit/control and button/control.
 */
final class TemplateNames
{
    /**
     * Validate presentation identifiers and order type and variant candidates.
     *
     * @return list<string>
     */
    public static function resolve(string $part, RenderNode $node): array
    {
        /** @var mixed $variant */
        $variant = $node->options['variant'] ?? null;
        /** @var mixed $name */
        foreach ([$part, $node->scope, $node->type, $variant ?? 'default'] as $name) {
            if (!is_string($name) || !preg_match('/^[a-z][a-z0-9_-]*$/D', $name)) {
                throw new InvalidArgumentException('Invalid template part, scope, type, or variant.');
            }
        }
        $prefix = $node->scope === 'field' ? '' : $node->scope . '/';
        $names = [$prefix . $node->type . '/' . $part, $prefix . $part];
        if ($variant !== null) {
            array_unshift($names, $prefix . 'variants/' . (string)$variant . '/' . $part);
        }

        return $names;
    }
}
