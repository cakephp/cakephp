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
use Cake\TestSuite\TestCase;

/**
 * VersionCommandTest class.
 */
class VersionCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();
    }

    /**
     * Test basic version output
     */
    public function testVersion(): void
    {
        $this->exec('version');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains(Configure::version());
    }

    /**
     * Test verbose output with stable version
     */
    public function testVerboseWithStableVersion(): void
    {
        $originalVersion = Configure::read('Cake.version');
        Configure::write('Cake.version', '5.2.9');

        $this->exec('version --verbose');

        Configure::write('Cake.version', $originalVersion);

        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('5.2.9');
        $this->assertOutputContains('https://github.com/cakephp/cakephp/releases/tag/5.2.9');
        $this->assertOutputContains('PHP:');
        $this->assertOutputContains(PHP_VERSION);
        $this->assertOutputContains(PHP_SAPI);
    }

    /**
     * Test verbose output with RC version (shows release link)
     */
    public function testVerboseWithRcVersion(): void
    {
        $originalVersion = Configure::read('Cake.version');
        Configure::write('Cake.version', '5.3.0-RC1');

        $this->exec('version --verbose');

        Configure::write('Cake.version', $originalVersion);

        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('5.3.0-RC1');
        $this->assertOutputContains('https://github.com/cakephp/cakephp/releases/tag/5.3.0-RC1');
        $this->assertOutputContains('PHP:');
    }

    /**
     * Test verbose output with dev version (no release link)
     */
    public function testVerboseWithDevVersion(): void
    {
        $originalVersion = Configure::read('Cake.version');
        Configure::write('Cake.version', '5.3.0-dev');

        $this->exec('version --verbose');

        Configure::write('Cake.version', $originalVersion);

        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('5.3.0-dev');
        $this->assertOutputNotContains('https://github.com/cakephp/cakephp/releases/tag/');
        $this->assertOutputContains('PHP:');
    }
}
