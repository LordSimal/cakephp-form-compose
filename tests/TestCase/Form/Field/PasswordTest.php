<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\View\Form\ArrayContext;
use FormCompose\Test\FormTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Protects inferred password controls from redisplaying bound credentials.
 */
class PasswordTest extends FormTestCase
{
    #[DataProvider('passwordPaths')]
    public function testPasswordNamesNeverRedisplaySubmittedValues(string $path): void
    {
        $secret = bin2hex(random_bytes(16));
        $helper = $this->helper(['password' => $secret, 'account' => ['passwd' => $secret]]);
        $helper->getView()->set('passwords', ['a' => 'A']);
        $form = $helper->form(new ArrayContext([
            'data' => ['passwd' => $secret, 'account' => ['password' => $secret]],
        ]))
            ->bind();
        $field = $form->field($path)->resolve();
        static::assertSame('password', $field->state->type);
        $form->open();
        $html = $field->control();
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//input[@type="password"])'));
        static::assertSame(0.0, $xpath->evaluate('count(//input/@value)'));
        static::assertStringNotContainsString($secret, $html);
        $form->close();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function passwordPaths(): array
    {
        return [
            'submitted' => ['password'],
            'context' => ['passwd'],
            'nested submitted' => ['account.passwd'],
            'nested context' => ['account.password'],
        ];
    }

    public function testExplicitValuesDefaultsAndChoicesCannotPrefillAnInferredPassword(): void
    {
        $table = new Table(['alias' => 'Accounts']);
        $table->setSchema(new TableSchema('accounts', ['password' => ['type' => 'string']]));

        $secret = bin2hex(random_bytes(16));
        $form = $this->helper()
            ->form(new Entity(['password' => $secret]))
            ->context(['table' => $table])
            ->bind();
        $form->open();

        $html = $form->control('password')
            ->value($secret)
            ->default($secret)
            ->choices([$secret => 'Choice'])
            ->render();
        static::assertStringContainsString('type="password"', $html);
        static::assertStringNotContainsString($secret, $html);
        static::assertStringNotContainsString('value=', $html);
        $form->close();
    }

    public function testExplicitTypesAndUnrelatedNamesRetainTheirMeaning(): void
    {
        $form = $this->helper()->form()->bind();
        static::assertSame('text', $form->field('password_hint')->resolve()->state->type);
        static::assertSame(
            'text',
            $form->field('password')->type('text')->resolve()->state->type,
        );
        static::assertSame(
            'hidden',
            $form->field('passwd')->type('hidden')->resolve()->state->type,
        );
        $form->open();
        $html = $form->control('password')->type('text')->value('deliberate-text')->render();
        static::assertStringContainsString('type="text" value="deliberate-text"', $html);
        $form->close();
    }

    public function testQueryValuesRemainAvailableInStateButNeverInPasswordMarkup(): void
    {
        $helper = $this->helper();
        $secret = bin2hex(random_bytes(16));
        $helper->getView()->setRequest($helper->getView()
            ->getRequest()
            ->withQueryParams(['password' => $secret]));
        $form = $helper->form()
            ->valueSources(['query'])
            ->bind();
        $field = $form->field('password')->resolve();
        static::assertSame($secret, $field->state->value);
        $form->open();
        static::assertStringNotContainsString($secret, $field->input()->render());
        $form->close();
    }
}
