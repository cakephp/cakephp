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
use Cake\TestSuite\TestCase;

/**
 * PluginListCommandTest class.
 */
class PluginListCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected string $configPath;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->setAppNamespace();
        $this->configPath = ROOT . DS . 'cakephp-plugins.php';
        if (file_exists($this->configPath)) {
            unlink($this->configPath);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->configPath)) {
            unlink($this->configPath);
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
        'TestPlugin' => '/config/path',
        'OtherPlugin' => '/config/path'
    ]
];
PHP;
        file_put_contents($this->configPath, $file);

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
        file_put_contents($this->configPath, $file);

        $this->exec('plugin list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertErrorContains('No plugins have been found.');
    }
}
