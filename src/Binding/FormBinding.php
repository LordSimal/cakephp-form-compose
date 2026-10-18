<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Http\ServerRequest;
use Cake\View\Form\ContextInterface;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Configuration\FormConfig;
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

    /** Combined field and embed index supplying defaults from every selected blueprint. */
    public ?FormDefinition $definition;

    /**
     * Capture binding inputs independently of rendering state.
     *
     * @param list<\FormCompose\Blueprint\FormDefinition> $definitions Compiled blueprints retaining their individual layouts.
     * @throws \InvalidArgumentException For duplicate declaration paths, malformed password field names, or type mappings.
     */
    public function __construct(
        public ContextInterface $context,
        public ServerRequest $request,
        public string $id,
        public FormConfig $config = new FormConfig(),
        public array $definitions = [],
    ) {
        $this->definition = match (count($this->definitions)) {
            0 => null,
            1 => $this->definitions[0],
            default => new FormDefinition(
                array_merge(...array_column($this->definitions, 'entries')),
                removed: array_merge(...array_column($this->definitions, 'removed')),
            ),
        };
        /** @var mixed $names */
        $names = $config->passwordFields;
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
            $config->typeMap,
        ));
    }
}
