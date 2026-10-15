<?php
declare(strict_types=1);

namespace FormCompose\Security;

use Cake\Core\Configure;
use Cake\Form\FormProtector;
use Cake\Http\ServerRequest;
use FormCompose\Form\FormState;
use FormCompose\Rendering\Attributes;

/**
 * Version-tested integration with Cake's form protection mechanism, independent of FormHelper.
 */
final class FormProtection
{
    private ?FormProtector $protector = null;

    /**
     * Capture the current request without starting a session or registering inputs.
     */
    public function __construct(private readonly ServerRequest $request)
    {
    }

    /**
     * Enable tamper protection only when the host application's component requested it.
     */
    public function start(FormState $state): void
    {
        /** @var array<string, mixed>|null $data */
        $data = $this->request->getAttribute('formTokenData');
        if ($state->method !== 'get' && $data !== null) {
            $this->request->getSession()->start();
            $this->protector = new FormProtector($data);
        }
    }

    /**
     * Register only successful rendered inputs and explicit locked hidden values.
     *
     * @param list<\FormCompose\Field\Submission> $submissions Actual submitted inputs.
     */
    public function register(array $submissions): void
    {
        foreach ($submissions as $submission) {
            $this->protector?->addField($submission->name, $submission->locked, $submission->value);
        }
    }

    /**
     * Named submit buttons are optional successful controls, matching native browser behavior.
     */
    public function optional(string $name): void
    {
        $this->protector?->unlockField($name);
    }

    /**
     * Return token values for hidden-field templates without embedding any markup.
     *
     * @return list<\FormCompose\Rendering\Attributes>
     */
    public function fields(FormState $state): array
    {
        if ($this->protector === null) {
            return [];
        }
        $data = $this->protector->buildTokenData($state->action, $this->request->getSession()->id());
        if (!Configure::read('debug')) {
            unset($data['debug']);
        }
        $fields = [];
        /** @var mixed $value */
        foreach ($data as $name => $value) {
            $fields[] = new Attributes(['type' => 'hidden', 'name' => '_Token[' . $name . ']', 'value' => $value]);
        }

        return $fields;
    }
}
