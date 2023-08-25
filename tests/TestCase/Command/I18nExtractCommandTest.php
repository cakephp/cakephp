<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Filesystem\Filesystem;
use Cake\TestSuite\TestCase;

/**
 * I18nExtractCommandTest
 */
class I18nExtractCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $path;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
        $this->setAppNamespace();

        $this->path = TMP . 'tests/extract_task_test';
        $fs = new Filesystem();
        $fs->deleteDir($this->path);
        $fs->mkdir($this->path . DS . 'locale');
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->deleteDir($this->path);
        $this->clearPlugins();
    }

    /**
     * testExecute method
     */
    public function testExecute(): void
    {
        $this->exec(
            'i18n extract ' .
            '--merge=no ' .
            '--extract-core=no ' .
            '--paths=' . TEST_APP . 'templates' . DS . 'Pages ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $result = file_get_contents($this->path . DS . 'default.pot');

        $this->assertFileDoesNotExist($this->path . DS . 'cake.pot');

        // The additional "./tests/test_app" is just due to the wonky folder structure of the test app.
        // In a regular app the path would start with "./templates".
        $pattern = '@\#: \./tests/test_app/templates/Pages/extract\.php:\d+\n';
        $pattern .= '\#: \./tests/test_app/templates/Pages/extract\.php:\d+\n';
        $pattern .= 'msgid "You have %d new message."\nmsgid_plural "You have %d new messages."@';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "You have %d new message."\nmsgstr ""/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result, 'No duplicate msgid');

        $pattern = '@\#: \./tests/test_app/templates/Pages/extract\.php:\d+\n';
        $pattern .= 'msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."@';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '@\#: \./tests/test_app/templates/Pages/extract\.php:\d+\nmsgid "';
        $pattern .= 'Hot features!';
        $pattern .= '\\\n - No Configuration: Set-up the database and let the magic begin';
        $pattern .= '\\\n - Extremely Simple: Just look at the name...It\'s Cake';
        $pattern .= '\\\n - Active, Friendly Community: Join us #cakephp on IRC. We\'d love to help you get started';
        $pattern .= '"\nmsgstr ""@';
        $this->assertMatchesRegularExpression($pattern, $result);

        $this->assertStringContainsString('msgid "double \\"quoted\\""', $result, 'Strings with quotes not handled correctly');
        $this->assertStringContainsString("msgid \"single 'quoted'\"", $result, 'Strings with quotes not handled correctly');

        $pattern = '@\#: \./tests/test_app/templates/Pages/extract\.php:\d+\n';
        $pattern .= 'msgctxt "mail"\n';
        $pattern .= 'msgid "letter"@';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '@\#: \./tests/test_app/templates/Pages/extract\.php:\d+\n';
        $pattern .= 'msgctxt "alphabet"\n';
        $pattern .= 'msgid "letter"@';
        $this->assertMatchesRegularExpression($pattern, $result);

        // extract.php - reading the domain.pot
        $result = file_get_contents($this->path . DS . 'domain.pot');

        $pattern = '/msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
        $pattern = '/msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);

        $pattern = '/msgid "You have %d new message \(domain\)."\nmsgid_plural "You have %d new messages \(domain\)."/';
        $this->assertMatchesRegularExpression($pattern, $result);
        $pattern = '/msgid "You deleted %d message \(domain\)."\nmsgid_plural "You deleted %d messages \(domain\)."/';
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    /**
     * testExecute with no paths
     */
    public function testExecuteNoPathOption(): void
    {
        $this->exec(
            'i18n extract ' .
            '--merge=no ' .
            '--extract-core=no ' .
            '--output=' . $this->path . DS,
            [
                TEST_APP . 'templates' . DS,
                'D',
            ]
        );
        $this->assertExitSuccess();
        $this->assertFileExists($this->path . DS . 'default.pot');
    }

    /**
     * testExecute with merging on method
     */
    public function testExecuteMerge(): void
    {
        $this->exec(
            'i18n extract ' .
            '--merge=yes ' .
            '--extract-core=no ' .
            '--paths=' . TEST_APP . 'templates' . DS . 'Pages ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $this->assertFileDoesNotExist($this->path . DS . 'cake.pot');
        $this->assertFileDoesNotExist($this->path . DS . 'domain.pot');
    }

    /**
     * test exclusions
     */
    public function testExtractWithExclude(): void
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--exclude=Pages,Layout ' .
            '--paths=' . TEST_APP . 'templates' . DS . ' ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\#: .*extract\.php:\d+\n/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);

        $pattern = '/\#: .*default\.php:\d+\n/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
    }

    /**
     * testExtractWithoutLocations method
     */
    public function testExtractWithoutLocations(): void
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--no-location=true ' .
            '--exclude=Pages,Layout ' .
            '--paths=' . TEST_APP . 'templates' . DS . ' ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();
        $this->assertFileExists($this->path . DS . 'default.pot');

        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\n\#: .*\n/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
    }

    /**
     * test extract can read more than one path.
     */
    public function testExtractMultiplePaths(): void
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--exclude=Pages,Layout ' .
            '--paths=' . TEST_APP . 'templates/Pages,' .
                TEST_APP . 'templates/Posts ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();
        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/msgid "Add User"/';
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    /**
     * Tests that it is possible to exclude plugin paths by enabling the param option for the ExtractTask
     */
    public function testExtractExcludePlugins(): void
    {
        static::setAppNamespace();
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--exclude-plugins=true ' .
            '--paths=' . TEST_APP . 'TestApp/ ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();

        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertDoesNotMatchRegularExpression('#TestPlugin#', $result);
    }

    /**
     * Test that is possible to extract messages from a single plugin
     */
    public function testExtractPlugin(): void
    {
        Configure::write('Plugins.autoload', ['TestPlugin']);

        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--plugin=TestPlugin ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();

        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertDoesNotMatchRegularExpression('#Pages#', $result);
        $this->assertMatchesRegularExpression('/translate\.php:\d+/', $result);
        $this->assertStringContainsString('This is a translatable string', $result);
    }

    /**
     * Test that is possible to extract messages from a vendored plugin.
     */
    public function testExtractVendoredPlugin(): void
    {
        $this->loadPlugins(['Company/TestPluginThree']);

        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--plugin=Company/TestPluginThree ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();

        $result = file_get_contents($this->path . DS . 'test_plugin_three.pot');
        $this->assertDoesNotMatchRegularExpression('#Pages#', $result);
        $this->assertMatchesRegularExpression('/default\.php:\d+/', $result);
        $this->assertStringContainsString('A vendor message', $result);
    }

    /**
     * Test that the extract shell overwrites existing files with the overwrite parameter
     */
    public function testExtractOverwrite(): void
    {
        file_put_contents($this->path . DS . 'default.pot', 'will be overwritten');
        $this->assertFileExists($this->path . DS . 'default.pot');
        $original = file_get_contents($this->path . DS . 'default.pot');

        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--overwrite ' .
            '--paths=' . TEST_APP . 'TestApp/ ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();

        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertNotEquals($original, $result);
    }

    /**
     *  Test that the extract shell scans the core libs
     */
    public function testExtractCore(): void
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=yes ' .
            '--paths=' . TEST_APP . 'TestApp/ ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();

        $this->assertFileExists($this->path . DS . 'cake.pot');
        $result = file_get_contents($this->path . DS . 'cake.pot');

        $pattern = '/#: Console\/Templates\//';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);

        $pattern = '/#: Test\//';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
    }

    /**
     * Test when marker-error option is set
     * When marker-error is unset, it's already test
     * with other functions like testExecute that not detects error because err never called
     */
    public function testMarkerErrorSets(): void
    {
        $this->exec(
            'i18n extract ' .
            '--marker-error ' .
            '--merge=no ' .
            '--extract-core=no ' .
            '--paths=' . TEST_APP . 'templates/Pages ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();
        $this->assertErrorContains('Invalid marker content in');
        $this->assertErrorContains('extract.php');
    }

    /**
     * test relative-paths option
     */
    public function testExtractWithRelativePaths(): void
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--paths=' . TEST_APP . 'templates ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $result = file_get_contents($this->path . DS . 'default.pot');

        $expected = '#: ./tests/test_app/templates/Pages/extract.php:';
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * test invalid path options
     */
    public function testExtractWithInvalidPaths(): void
    {
        $this->exec(
            'i18n extract ' .
            '--extract-core=no ' .
            '--paths=' . TEST_APP . 'templates,' . TEST_APP . 'unknown ' .
            '--output=' . $this->path . DS
        );
        $this->assertExitSuccess();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $result = file_get_contents($this->path . DS . 'default.pot');

        $expected = '#: ./tests/test_app/templates/Pages/extract.php:';
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * Test with associative arrays in App.path.locales and App.path.templates.
     */
    public function testExtractWithAssociativePaths(): void
    {
        Configure::write('App.paths', [
            'plugins' => ['customKey' => TEST_APP . 'Plugin' . DS],
            'templates' => ['customKey' => TEST_APP . 'templates' . DS],
            'locales' => ['customKey' => TEST_APP . 'resources' . DS . 'locales' . DS],
        ]);

        $this->exec(
            'i18n extract ' .
            '--merge=no ' .
            '--extract-core=no ',
            [
                // Sending two empty inputs so \Cake\Command\I18nExtractCommand::_getPaths()
                // loops through all paths
                '',
                '',
                'D',
                $this->path . DS,
            ]
        );
        $this->assertExitSuccess();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $result = file_get_contents($this->path . DS . 'default.pot');

        $expected = '#: ./tests/test_app/templates/Pages/extract.php:';
        $this->assertStringContainsString($expected, $result);
    }
}
