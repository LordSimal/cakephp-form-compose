<?php
declare(strict_types=1);

namespace FormCompose\Form;

use Cake\Routing\Router;
use FormCompose\Binding\FormBinding;
use InvalidArgumentException;

/**
 * Resolves native form actions through Cake routing without HTML helper dependencies.
 */
final class ActionUrl
{
    /**
     * Build an action or retain the current request target when action is omitted.
     *
     * @param array<string, mixed> $options Form creation options.
     * @return array{attribute: ?string, signed: string}
     */
    public static function resolve(FormBinding $binding, array $options): array
    {
        /** @var array|string|false|null $url */
        $url = $options['url'] ?? null;
        $action = $url === null || $url === false ? $binding->request->getRequestTarget() : Router::url($url);
        $parts = parse_url($action);
        if (!is_array($parts)) {
            throw new InvalidArgumentException('Invalid form action URL.');
        }
        $signed = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        return ['attribute' => $url === false ? null : $action, 'signed' => $signed];
    }
}
