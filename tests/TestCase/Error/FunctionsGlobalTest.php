<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Core\Configure;
use Cake\Error\Debugger;
use Cake\TestSuite\TestCase;
use Psy\Shell;

require_once CAKE . 'Error/functions_global.php';

/**
 * Test cases for functions in Error\functions_global.php
 */
class FunctionsGlobalTest extends TestCase
{
    /**
     * Tests that debug() prints the variable with the caller's location and returns it
     */
    public function testDebug(): void
    {
        ob_start();
        $this->assertSame('this-is-a-test', debug('this-is-a-test', false));
        $result = ob_get_clean();
        $expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'this-is-a-test'
###########################

EXPECTED;
        $expected = sprintf($expectedText, Debugger::trimPath(__FILE__), __LINE__ - 9);
        $this->assertSame($expected, $result);

        ob_start();
        debug('this-is-a-test', false, false);
        $result = ob_get_clean();
        $this->assertStringNotContainsString('(line', $result);
    }

    /**
     * Tests that debug() is silent when debug mode is off
     */
    public function testDebugDisabled(): void
    {
        Configure::write('debug', false);

        ob_start();
        $this->assertSame('this-is-a-test', debug('this-is-a-test'));
        $this->assertSame('', ob_get_clean());
    }

    /**
     * Tests that stackTrace() is a shortcut for Debugger::trace()
     */
    public function testStackTrace(): void
    {
        ob_start();
        // phpcs:ignore
        stackTrace(); $expected = Debugger::trace();
        $this->assertSame($expected, ob_get_clean());

        Configure::write('debug', false);
        ob_start();
        stackTrace();
        $this->assertSame('', ob_get_clean());
    }

    /**
     * Tests that dd() returns without output or exiting when debug mode is off
     */
    public function testDdDisabled(): void
    {
        Configure::write('debug', false);

        ob_start();
        dd('this-is-a-test');
        $this->assertSame('', ob_get_clean());
    }

    /**
     * Tests that breakpoint() warns when psy/psysh is not installed
     */
    public function testBreakpointWithoutPsysh(): void
    {
        $this->skipIf(class_exists(Shell::class), 'psy/psysh is installed.');

        $result = 'not-called';
        $error = $this->captureError(E_USER_WARNING, function () use (&$result): void {
            $result = breakpoint();
        });
        $this->assertNull($result);
        $this->assertStringContainsString('psy/psysh must be installed', $error->getMessage());
    }
}
