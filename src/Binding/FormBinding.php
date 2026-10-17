<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Http\ServerRequest;
use Cake\View\Form\ContextInterface;
use FormCompose\Blueprint\FormDefinition;
use InvalidArgumentException;

/**
 * Holds the data sources and configuration shared by one form session.
 *
 * The CakePHP context supplies schema, entity values, and validation errors; the
 * request supplies submitted data and security metadata. FieldResolver and
 * FormConstruction read this object to prepare fields and form metadata.
 */
final readonly class FormBinding
{
    public const array DEFAULT_PASSWORD_FIELDS = ['password', 'passwd'];

    /** @var list<string> Terminal field names that infer password inputs. */
    public array $passwordFields;

    /** @var array<string, string> Schema types mapped to native or registered control aliases. */
    public array $typeMap;

    /**
     * Capture binding inputs independently of rendering state.
     *
     * @param array<string, mixed> $options Form creation options, valueSources, passwordFields, and typeMap.
     * @throws \InvalidArgumentException For malformed password field names or type mappings.
     */
    public function __construct(
        public ContextInterface $context,
        public ServerRequest $request,
        public string $id,
        public array $options = [],
        public ?FormDefinition $definition = null,
    ) {
        /** @var mixed $names */
        $names = array_key_exists('passwordFields', $options)
            ? $options['passwordFields']
            : self::DEFAULT_PASSWORD_FIELDS;
        if (!is_array($names) || !array_is_list($names)) {
            throw new InvalidArgumentException('The passwordFields option must be a list of terminal field names.');
        }

        $passwordFields = [];
        /** @var mixed $name */
        foreach ($names as $name) {
            if (!is_string($name) || !preg_match('/^[a-zA-Z0-9_-]+$/D', $name)) {
                throw new InvalidArgumentException('Each passwordFields entry must be a nonempty terminal field name.');
            }

            $passwordFields[] = $name;
        }

        $this->passwordFields = $passwordFields;
        $this->typeMap = array_replace(TypeMap::DEFAULTS, TypeMap::normalize(
            array_key_exists('typeMap', $options) ? $options['typeMap'] : [],
        ));
    }
}
