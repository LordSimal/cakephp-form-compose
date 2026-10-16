<?php
declare(strict_types=1);

namespace FormCompose\Test;

use Cake\Http\ServerRequest;
use Cake\View\View;
use Dom\HTMLDocument;
use Dom\XPath;
use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use FormCompose\FormSession;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\Theme;
use FormCompose\View\Helper\FormHelper;
use PHPUnit\Framework\TestCase;
use const Dom\HTML_NO_DEFAULT_NS;

/**
 * Creates real Cake views and requests for form integration tests.
 */
abstract class FormTestCase extends TestCase
{
    /**
     * Build a real helper using optional submitted data and request attributes.
     *
     * @param array<string, mixed> $data Submitted body.
     * @param array<string, mixed> $attributes Request attributes.
     */
    protected function helper(array $data = [], array $attributes = []): FormHelper
    {
        $request = new ServerRequest(['url' => '/articles/save', 'environment' => ['REQUEST_METHOD' => 'POST']]);
        $request = $request->withParsedBody($data);
        /** @var mixed $value */
        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return new FormHelper(new View($request));
    }

    /**
     * Bind an internal compiled snapshot for configuration and renderer unit tests.
     *
     * Application-facing tests use blueprint class names through FormHelper::bind().
     * @param array<string, mixed> $options Binding options.
     */
    protected function bindCompiled(
        FormDefinition $definition,
        ?FormHelper $helper = null,
        mixed $data = null,
        array $options = [],
    ): FormSession {
        $helper ??= $this->helper();
        $base = $helper->bind($data, $options);
        $binding = $base->binding;
        /** @var mixed $factory */
        $factory = $helper->getConfig('blueprintFactory');
        $factory = $factory instanceof BlueprintFactory
            ? $factory
            : new BlueprintFactory();
        $definition = $factory->expand($definition, FormContext::fromBinding($binding));
        $theme = Theme::base();

        /** @var array<string, \FormCompose\Field\FieldTypeInterface> $types */
        $types = $options['types'] ?? [];

        return new FormSession(
            new FormBinding(
                $binding->context,
                $binding->request,
                $binding->id,
                $binding->options,
                $definition,
            ),
            new RenderContext(
                new PhpRenderer($helper->getView(), $theme),
                $theme,
                $factory->lazyTypes($types),
                $helper->getView()->get(...),
            ),
            $factory,
        );
    }

    /**
     * Parse rendered HTML5 fragments with UTF-8 encoding and unprefixed XPath queries.
     */
    protected function xpath(string $html): XPath
    {
        $document = HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>',
            HTML_NO_DEFAULT_NS,
        );

        return new XPath($document);
    }
}
