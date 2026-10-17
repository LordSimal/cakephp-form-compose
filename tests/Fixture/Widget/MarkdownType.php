<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Widget;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Field\SingleInputType;

/**
 * Keeps one native textarea usable before an optional editor enhancement loads.
 */
#[AsFieldType('markdown')]
class MarkdownType extends SingleInputType
{
    protected ?string $template = 'widgets/markdown';
}
