<?php
declare(strict_types=1);

namespace FormCompose\Test;

use Cake\Http\ServerRequest;
use Cake\View\View;
use Dom\HTMLDocument;
use Dom\XPath;
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
