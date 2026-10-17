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
        $form = $helper->bind(new ArrayContext([
            'data' => ['passwd' => $secret, 'account' => ['password' => $secret]],
        ]));
        $field = $form->field($path);
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
        $form = $this->helper()->bind(new Entity(['password' => $secret]), ['context' => ['table' => $table]]);
        $form->open();

        $html = $form->control('password', [
            'value' => $secret,
            'default' => $secret,
            'options' => [$secret => 'Choice'],
        ]);
        static::assertStringContainsString('type="password"', $html);
        static::assertStringNotContainsString($secret, $html);
        static::assertStringNotContainsString('value=', $html);
        $form->close();
    }

    public function testExplicitTypesAndUnrelatedNamesRetainTheirMeaning(): void
    {
        $form = $this->helper()->bind();
        static::assertSame('text', $form->field('password_hint')->state->type);
        static::assertSame('text', $form->field('password', ['type' => 'text'])->state->type);
        static::assertSame('hidden', $form->field('passwd', ['type' => 'hidden'])->state->type);
        $form->open();
        $html = $form->control('password', ['type' => 'text', 'value' => 'deliberate-text']);
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
        $form = $helper->bind(null, ['valueSources' => ['query']]);
        $field = $form->field('password');
        static::assertSame($secret, $field->state->value);
        $form->open();
        static::assertStringNotContainsString($secret, $field->input());
        $form->close();
    }
}
