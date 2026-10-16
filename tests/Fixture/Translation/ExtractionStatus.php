<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use Cake\Database\Type\Attribute\Label;
use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumLabelTrait;

/**
 * Preserves CakePHP's own enum-label extraction alongside blueprint messages.
 */
enum ExtractionStatus: string implements EnumLabelInterface
{
    use EnumLabelTrait;

    #[Label('Published status', domain: 'statuses', context: 'workflow')]
    case Published = 'published';
}
