<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use RuntimeException;
use function Cake\TestSuite\enablePluginLoadingForTests;

/**
 * Tests for the global plugin loading function in src/TestSuite/functions.php
 */
class PluginBootstrapTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test that enablePluginLoadingForTests stores plugin configuration
     *
     * @return void
     */
    public function testEnablePluginLoadingForTests(): void
    {
        // Create a test plugins.php file
        $testConfigDir = TMP . 'test_config' . DS;
        if (!is_dir($testConfigDir)) {
            mkdir($testConfigDir, 0777, true);
        }

        file_put_contents(
            $testConfigDir . 'plugins.php',
            '<?php return ["TestPlugin" => ["bootstrap" => true]];',
        );

        // Clear any existing configuration
        Configure::delete('Test.plugins');

        // Enable plugin loading for tests
        enablePluginLoadingForTests($testConfigDir);

        // Check that the configuration was stored
        $stored = Configure::read('Test.plugins');
        $this->assertIsArray($stored);
        $this->assertArrayHasKey('TestPlugin', $stored);
        $this->assertEquals(['bootstrap' => true], $stored['TestPlugin']);

        // Clean up
        unlink($testConfigDir . 'plugins.php');
        rmdir($testConfigDir);
    }

    /**
     * Test that loadAllPlugins method reads from configuration
     *
     * @return void
     */
    public function testLoadAllPluginsWithConfiguredPlugins(): void
    {
        // Set up plugin configuration
        Configure::write('Test.plugins', [
            'TestPlugin' => ['bootstrap' => false],
            'TestPluginTwo' => ['bootstrap' => true],
        ]);

        // Clear any existing state
        Plugin::getCollection()->clear();
        $this->appPluginsToLoad = [];

        // Load all plugins using the TestCase method
        $result = $this->loadAllPlugins();

        // Check that the method returns $this for chaining
        $this->assertSame($this, $result);

        // When using IntegrationTestTrait, loadAllPlugins sets appPluginsToLoad
        $this->assertArrayHasKey('TestPlugin', $this->appPluginsToLoad);
        $this->assertArrayHasKey('TestPluginTwo', $this->appPluginsToLoad);
        $this->assertEquals(['bootstrap' => false], $this->appPluginsToLoad['TestPlugin']);
        $this->assertEquals(['bootstrap' => true], $this->appPluginsToLoad['TestPluginTwo']);
    }

    /**
     * Test that enablePluginLoadingForTests throws exception for missing file
     *
     * @return void
     */
    public function testEnablePluginLoadingForTestsWithMissingFile(): void
    {
        // Clear any existing configuration
        Configure::delete('Test.plugins');

        // Expect exception when plugins.php file doesn't exist
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to load plugins list');

        // Try to enable with non-existent path
        enablePluginLoadingForTests('/non/existent/path/');
    }

    /**
     * Test that enablePluginLoadingForTests raises error for invalid plugins.php return value
     *
     * @return void
     */
    public function testEnablePluginLoadingForTestsWithInvalidReturn(): void
    {
        // Create a test plugins.php file that returns non-array
        $testConfigDir = TMP . 'test_invalid_config' . DS;
        if (!is_dir($testConfigDir)) {
            mkdir($testConfigDir, 0777, true);
        }

        file_put_contents(
            $testConfigDir . 'plugins.php',
            '<?php return "not an array";',
        );

        // Clear any existing configuration
        Configure::delete('Test.plugins');

        // Expect exception when plugins.php returns non-array
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The plugins configuration file');
        $this->expectExceptionMessage('must return an array');

        enablePluginLoadingForTests($testConfigDir);

        // Clean up (won't reach here due to exception)
        unlink($testConfigDir . 'plugins.php');
        rmdir($testConfigDir);
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Plugin::getCollection()->clear();
        Configure::delete('Test.plugins');
    }
}
