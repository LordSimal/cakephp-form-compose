<?php
declare(strict_types=1);

namespace FormCompose\Command;

use Cake\Command\I18nExtractCommand as CakeI18nExtractCommand;
use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;
use FormCompose\Translated;
use ReflectionClass;

/**
 * Extends CakePHP's catalog extraction with declarative blueprint messages.
 *
 * Plugin command discovery exposes form_compose.i18n extract alongside Cake's
 * command. Normal translation functions and enum labels use Cake's extraction;
 * this hook also inspects Field attributes without executing blueprint hooks,
 * text callbacks, or translating the stored messages.
 */
class I18nExtractCommand extends CakeI18nExtractCommand
{
    /**
     * Add explicit label/help messages to CakePHP's existing POT output pipeline.
     */
    protected function extractFileReflection(string $file, string $code): void
    {
        parent::extractFileReflection($file, $code);
        $class = $this->parseClassName($code);
        if ($class === null || !class_exists($class, false) || !is_subclass_of($class, FormBlueprint::class)) {
            return;
        }

        $reflection = new ReflectionClass($class);
        foreach ($reflection->getAttributes(Field::class) as $attribute) {
            $options = $attribute->newInstance()->definition()->options;
            foreach (['label', 'help'] as $key) {
                /** @var mixed $text */
                $text = $options[$key] ?? null;
                if (!$text instanceof Translated) {
                    continue;
                }

                $this->addTranslation($text->domain, $text->message, [
                    'file' => '.' . str_replace((string)ROOT, '', $file),
                    'line' => $reflection->getStartLine(),
                    'msgctxt' => $text->context,
                ]);
            }
        }
    }
}
