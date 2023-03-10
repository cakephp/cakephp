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
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Error\Debugger;
use Cake\TestSuite\TestCase;
use function Cake\Core\pj;
use function Cake\Core\pr;
use function Cake\Error\debug;
use function Cake\Error\stackTrace;

/**
 * FunctionsTest class
 */
class FunctionsTest extends TestCase
{
    /**
     * test debug()
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
        $value = '<div>this-is-a-test</div>';
        $this->assertSame($value, debug($value, true));
        $result = ob_get_clean();
        $this->assertStringContainsString('<div class="cake-debug-output', $result);
        $this->assertStringContainsString('this-is-a-test', $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true, true);
        $result = ob_get_clean();
        $expected = <<<EXPECTED
<div class="cake-debug-output cake-debug" style="direction:ltr">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
EXPECTED;
        $expected = sprintf($expected, Debugger::trimPath(__FILE__), __LINE__ - 6);
        $this->assertStringContainsString($expected, $result);

        ob_start();
        debug('<div>this-is-a-test</div>', true, false);
        $result = ob_get_clean();
        $this->assertStringNotContainsString('(line', $result);
    }

    /**
     * test pr()
     */
    public function testPr(): void
    {
        ob_start();
        $this->assertTrue(pr(true));
        $result = ob_get_clean();
        $expected = "\n1\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertFalse(pr(false));
        $result = ob_get_clean();
        $expected = "\n\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertNull(pr(null));
        $result = ob_get_clean();
        $expected = "\n\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertSame(123, pr(123));
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pr('123');
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pr('this is a test');
        $result = ob_get_clean();
        $expected = "\nthis is a test\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pr(['this' => 'is', 'a' => 'test', 123 => 456]);
        $result = ob_get_clean();
        $expected = "\nArray\n(\n    [this] => is\n    [a] => test\n    [123] => 456\n)\n\n";
        $this->assertSame($expected, $result);
    }

    /**
     * test pj()
     */
    public function testPj(): void
    {
        ob_start();
        $this->assertTrue(pj(true));
        $result = ob_get_clean();
        $expected = "\ntrue\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertFalse(pj(false));
        $result = ob_get_clean();
        $expected = "\nfalse\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertNull(pj(null));
        $result = ob_get_clean();
        $expected = "\nnull\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $this->assertSame(123, pj(123));
        $result = ob_get_clean();
        $expected = "\n123\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pj('123');
        $result = ob_get_clean();
        $expected = "\n\"123\"\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        pj('this is a test');
        $result = ob_get_clean();
        $expected = "\n\"this is a test\"\n\n";
        $this->assertSame($expected, $result);

        ob_start();
        $value = ['this' => 'is', 'a' => 'test', 123 => 456];
        $this->assertSame($value, pj($value));
        $result = ob_get_clean();
        $expected = "\n{\n    \"this\": \"is\",\n    \"a\": \"test\",\n    \"123\": 456\n}\n\n";
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that the stackTrace() method is a shortcut for Debugger::trace()
     */
    public function testStackTrace(): void
    {
        ob_start();
        // phpcs:ignore
        stackTrace(); $expected = Debugger::trace();
        $result = ob_get_clean();
        $this->assertSame($expected, $result);

        $opts = ['args' => true];
        ob_start();
        // phpcs:ignore
        stackTrace($opts); $expected = Debugger::trace($opts);
        $result = ob_get_clean();
        $this->assertSame($expected, $result);

        $opts = ['format' => 'array'];
        $trace = Debugger::trace($opts);
        $this->assertEmpty(array_column($trace, 'args'));
    }
}
