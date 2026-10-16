<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Command;

use Cake\Command\I18nExtractCommand as CakeI18nExtractCommand;
use Cake\Console\CommandCollection;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleInput;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Utility\Filesystem;
use FormCompose\Command\I18nExtractCommand;
use FormCompose\FormComposePlugin;
use FormCompose\Test\Fixture\Translation\ExtractionForm;
use FormCompose\Test\Fixture\Translation\ExtractionStatus;
use FormCompose\Test\FormTestCase;
use ReflectionClass;

/**
 * Exercises the plugin command through real argument parsing and POT file generation.
 */
class I18nExtractCommandTest extends FormTestCase
{
    public function testPluginDiscoveryPreservesTheCoreCommand(): void
    {
        $commands = new CommandCollection();
        $commands->add('i18n extract', CakeI18nExtractCommand::class);
        $commands = new FormComposePlugin()->console($commands);
        static::assertSame(CakeI18nExtractCommand::class, $commands->get('i18n extract'));
        static::assertSame(I18nExtractCommand::class, $commands->get('form_compose.i18n extract'));
    }

    public function testCatalogIncludesDeclarativeMessagesAndExistingCakeMessagesWithoutExecutingHooks(): void
    {
        $directory = TMP . 'translation-extraction/';
        $filesystem = new Filesystem();
        $filesystem->mkdir($directory);

        $output = new StubConsoleOutput();
        $io = new ConsoleIo($output, new StubConsoleOutput(), new StubConsoleInput([]));
        $file = new ReflectionClass(ExtractionForm::class)->getFileName();
        static::assertIsString($file);
        $enumFile = new ReflectionClass(ExtractionStatus::class)->getFileName();
        static::assertIsString($enumFile);
        $functionFile = $directory . 'functions.php';
        $unloadedFile = $directory . 'unloaded.php';
        file_put_contents($unloadedFile, '<?php class UnloadedTranslationExtraction {}');
        file_put_contents($functionFile, "<?php __('A runtime message');\n");
        try {
            $status = new I18nExtractCommand()->run([
                '--paths',
                dirname($file),
                '--files',
                implode(',', [$file, $enumFile, $functionFile, $unloadedFile]),
                '--output',
                $directory,
                '--merge',
                'no',
                '--extract-core',
                'no',
                '--overwrite',
            ], $io);
            static::assertSame(0, $status);
            $blueprints = file_get_contents($directory . 'blueprints.pot');
            static::assertIsString($blueprints);
            static::assertStringContainsString('msgctxt "heading"', $blueprints);
            static::assertStringContainsString('msgid "Catalog title"', $blueprints);
            static::assertStringContainsString('tests/Fixture/Translation/ExtractionForm.php:', $blueprints);
            $default = file_get_contents($directory . 'default.pot');
            static::assertIsString($default);
            static::assertStringContainsString('msgid "Catalog help"', $default);
            static::assertStringContainsString('msgid "A runtime message"', $default);
            static::assertStringNotContainsString('Discarded title', $default);
            $statuses = file_get_contents($directory . 'statuses.pot');
            static::assertIsString($statuses);
            static::assertStringContainsString('msgid "Published status"', $statuses);
            static::assertStringContainsString('msgctxt "workflow"', $statuses);
        } finally {
            $filesystem->deleteDir($directory);
        }
    }
}
