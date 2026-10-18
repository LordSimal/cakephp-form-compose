<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\ButtonConfigBuilder;
use FormCompose\Builder\SummaryConfigBuilder;
use FormCompose\Rendering\Html;

/** Layout settings for form configuration.
 * @require-extends \FormCompose\Configuration\Configuration
 */
trait FormLayoutSettingsTrait
{
    /** The configured errorSummary setting. */
    public bool|SummaryConfig $errorSummary {
        get {
            /** @var mixed $value */
            $value = $this->values['errorSummary'] ?? false;
            assert(
                is_bool($value) || $value instanceof SummaryConfig,
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** Return configuration with an explicit errorSummary setting. */
    public function withErrorSummary(bool|SummaryConfig|SummaryConfigBuilder $value): static
    {
        return $this->changed('errorSummary', $value instanceof SummaryConfigBuilder ? $value->config() : $value);
    }

    /** The configured submit setting. */

    public string|Html|false|null $submit {
        get {
            /** @var mixed $value */
            $value = $this->values['submit'] ?? null;
            assert(
                $value === null || $value === false || is_string($value) || $value instanceof Html,
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** Return configuration with an explicit submit setting. */
    public function withSubmit(string|Html|false|null $value): static
    {
        return $this->changed('submit', $value);
    }

    /** The configured submitConfig setting. */
    public ButtonConfig $submitConfig {
        get {
            /** @var mixed $value */
            $value = $this->values['submitConfig'] ?? new ButtonConfig();
            assert($value instanceof ButtonConfig, 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit submitConfig setting. */
    public function withSubmitConfig(ButtonConfig|ButtonConfigBuilder $value): static
    {
        return $this->changed('submitConfig', ButtonConfig::capture($value));
    }
}
