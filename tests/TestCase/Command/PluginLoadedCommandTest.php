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
 * @since         3.1.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * PluginLoadedCommand test.
 */
class PluginLoadedCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->useCommandRunner();
        $this->setAppNamespace();
    }

    /**
     * Tests that list of loaded plugins is shown with loaded command.
     */
    public function testLoaded(): void
    {
        $expected = Plugin::loaded();

        $this->exec('plugin loaded');
        $this->assertExitCode(Command::CODE_SUCCESS);

        foreach ($expected as $value) {
            $this->assertOutputContains($value);
        }
    }
}
