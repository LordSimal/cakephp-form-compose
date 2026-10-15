<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Html;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    public function testMergingPreservesClassesAndDescriptionReferences(): void
    {
        $base = new Attributes(['class' => 'input', 'aria-describedby' => 'hint', 'title' => 'old']);
        $merged = $base->merge([
            'class' => ['wide', 'input' => true, 'hidden' => false],
            'aria-describedby' => 'error hint',
            'title' => null,
        ]);
        static::assertSame(' class="input wide" aria-describedby="hint error"', (string)$merged);
        static::assertSame('input', $base->values['class']);
        static::assertSame(
            ' class="replacement" aria-describedby="hint" title="old"',
            (string)$base->replace(['class' => 'replacement']),
        );
    }

    public function testEscapingBooleansAndJavascriptAttributes(): void
    {
        $attributes = new Attributes([
            'disabled' => true,
            'hidden' => false,
            'title' => '"<>&',
            '@click' => 'save()',
            ':class' => 'classes',
            'hx-post' => '/save',
        ]);
        static::assertSame(
            ' disabled title="&quot;&lt;&gt;&amp;" @click="save()" :class="classes" hx-post="/save"',
            (string)$attributes,
        );
        static::assertSame('<em>safe</em>', Html::text(new Html('<em>safe</em>')));
    }

    public function testInvalidAttributeNamesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Attributes(['onclick="bad' => 'value']);
    }
}
