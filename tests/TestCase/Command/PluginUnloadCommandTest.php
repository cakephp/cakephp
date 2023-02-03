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
 * PluginUnloadCommandTest class
 */
class PluginUnloadCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $configFile;

    /**
     * @var string
     */
    protected $originalContent;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->configFile = CONFIG . 'plugins.php';
        $this->originalContent = file_get_contents($this->configFile);

        $contents = <<<CONTENTS
        <?php
        return [
            'TestPlugin' => ['routes' => false],
            'TestPluginTwo',
            'Company/TestPluginThree'
        ];
        CONTENTS;

        file_put_contents($this->configFile, $contents);

        $this->setAppNamespace();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();

        file_put_contents($this->configFile, $this->originalContent);
    }

    /**
     * testUnload
     *
     * @dataProvider pluginNameProvider
     */
    public function testUnload($plugin): void
    {
        $this->exec('plugin unload ' . $plugin);

        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $contents = file_get_contents($this->configFile);

        $this->assertStringNotContainsString("'" . $plugin . "'", $contents);
        $this->assertStringContainsString("'Company/TestPluginThree'", $contents);
    }

    public static function pluginNameProvider()
    {
        return [
            ['TestPlugin'],
            ['TestPluginTwo'],
        ];
    }

    public function testUnloadNoConfigFile(): void
    {
        unlink($this->configFile);

        $this->exec('plugin unload TestPlugin');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('`CONFIG/plugins.php` not found or does not return an array');
    }

    public function testUnloadUnknownPlugin(): void
    {
        $this->exec('plugin unload NopeNotThere');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('Plugin `NopeNotThere` could not be found');
    }
}
