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
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\CommandScanner;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Test case for the CommandScanner
 */
class CommandScannerTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * The `file` value of a scanned plugin command must point at the real
     * file, i.e. include the `Command` directory separator. Regression test
     * for a missing separator that produced paths like
     * `.../src/CommandFooCommand.php`.
     */
    public function testScanPluginFilePath(): void
    {
        $this->loadPlugins(['Company/TestPluginThree']);

        $expectedFile = Plugin::classPath('Company/TestPluginThree')
            . 'Command' . DIRECTORY_SEPARATOR . 'CompanyCommand.php';

        $commandScanner = new CommandScanner();
        $result = $commandScanner->scanPlugin('Company/TestPluginThree');

        $this->assertSame($expectedFile, $result[0]['file']);
    }

    /**
     * A non-existent plugin yields no commands.
     */
    public function testScanPluginNonExistentPlugin(): void
    {
        $commandScanner = new CommandScanner();
        $this->assertSame([], $commandScanner->scanPlugin('NonExistentPlugin'));
    }
}
