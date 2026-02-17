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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\PluginConfig;
use Cake\TestSuite\TestCase;

/**
 * PluginListCommandTest class.
 */
class PluginListCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use PluginConfigFileTrait;

    protected string $pluginsListPath;

    protected string $pluginsConfigPath;

    protected string $originalPluginsConfigContent = '';

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setAppNamespace();
        $this->pluginsListPath = ROOT . DS . 'cakephp-plugins.php';
        if (file_exists($this->pluginsListPath)) {
            $this->deletePhpFile($this->pluginsListPath);
        }
        $this->pluginsConfigPath = CONFIG . 'plugins.php';
        if (file_exists($this->pluginsConfigPath)) {
            $this->originalPluginsConfigContent = file_get_contents($this->pluginsConfigPath);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('plugins');
        PluginConfig::clearCache();
        if (file_exists($this->pluginsListPath)) {
            $this->deletePhpFile($this->pluginsListPath);
        }
        if (file_exists($this->pluginsConfigPath)) {
            $this->writePhpFile($this->pluginsConfigPath, $this->originalPluginsConfigContent);
        }
    }

    /**
     * Test generating help succeeds
     */
    public function testHelp(): void
    {
        $this->exec('plugin list --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('plugin list');
    }

    /**
     * Test plugin names are being displayed correctly
     */
    public function testList(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'TestPlugin' => '/config/path/',
        'OtherPlugin' => '/config/path/'
    ]
];
PHP;
        $this->writePhpFile($this->pluginsListPath, $file);

        $this->exec('plugin list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestPlugin');
        $this->assertOutputContains('OtherPlugin');
    }

    /**
     * Test empty plugins array
     */
    public function testListEmpty(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [];
PHP;
        $this->writePhpFile($this->pluginsListPath, $file);

        $this->exec('plugin list');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('No plugins have been found.');
    }

    /**
     * Test enabled plugins are being flagged as enabled
     */
    public function testListEnabled(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'TestPlugin' => '/config/path/',
        'OtherPlugin' => '/config/path/'
    ]
];
PHP;
        $this->writePhpFile($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
declare(strict_types=1);
return [
    'TestPlugin',
    'OtherPlugin' => ['onlyDebug' => true, 'onlyCli' => true, 'optional' => true]
];
PHP;
        $this->writePhpFile($this->pluginsConfigPath, $config);

        $this->exec('plugin list');

        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestPlugin');
        $this->assertOutputContains('OtherPlugin');
    }

    /**
     * Test listing unknown plugins throws an exception
     */
    public function testListUnknown(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'TestPlugin' => '/config/path/',
        'OtherPlugin' => '/config/path/'
    ]
];
PHP;
        $this->writePhpFile($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
declare(strict_types=1);
return [
    'Unknown'
];
PHP;
        $this->writePhpFile($this->pluginsConfigPath, $config);

        $this->expectException(MissingPluginException::class);
        $this->expectExceptionMessage('Plugin `Unknown` could not be found.');

        $this->exec('plugin list');
    }

    /**
     * Test listing vendor plugins with versions
     */
    public function testListWithVersions(): void
    {
        $pluginsPath = TMP . 'plugin-list-command-test';
        $chronosPath = $pluginsPath . DS . 'chronos';
        $codeSnifferPath = $pluginsPath . DS . 'cakephp-codesniffer';
        if (!is_dir($chronosPath)) {
            mkdir($chronosPath, 0777, true);
        }
        if (!is_dir($codeSnifferPath)) {
            mkdir($codeSnifferPath, 0777, true);
        }
        file_put_contents(
            $chronosPath . DS . 'composer.json',
            json_encode(['name' => 'cakephp/chronos'], JSON_THROW_ON_ERROR),
        );
        file_put_contents(
            $codeSnifferPath . DS . 'composer.json',
            json_encode(['name' => 'cakephp/cakephp-codesniffer'], JSON_THROW_ON_ERROR),
        );

        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'Chronos' => TMP . 'plugin-list-command-test' . DS . 'chronos',
        'CodeSniffer' => TMP . 'plugin-list-command-test' . DS . 'cakephp-codesniffer'
    ]
];
PHP;
        $this->writePhpFile($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
declare(strict_types=1);
return [
    'Chronos' => ['optional' => true],
    'CodeSniffer' => ['optional' => true]
];
PHP;
        $this->writePhpFile($this->pluginsConfigPath, $config);

        $path = ROOT . DS . 'tests' . DS . 'composer.lock';
        $this->exec(sprintf('plugin list --composer-path="%s"', $path));
        $this->assertOutputContains('Chronos');
        $this->assertOutputContains('3.0.4');
        $this->assertOutputContains('CodeSniffer');
        $this->assertOutputContains('5.1.1');
    }
}
