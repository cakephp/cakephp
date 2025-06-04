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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command\Helper;

use Cake\Command\Helper\BannerHelper;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * BannerHelper test.
 */
class BannerHelperTest extends TestCase
{
    /**
     * @var \Cake\Command\Helper\BannerHelper
     */
    protected BannerHelper $helper;

    /**
     * @var \Cake\Console\TestSuite\StubConsoleOutput
     */
    protected StubConsoleOutput $stub;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected ConsoleIo $io;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->stub = new StubConsoleOutput();
        $this->io = new ConsoleIo($this->stub);
        $this->helper = new BannerHelper($this->io);
    }

    /**
     * Test that the callback is invoked until 100 is reached.
     */
    public function testOutputInvalidPadding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper->withPadding(-1);
    }

    /**
     * Test output with all options
     */
    public function testOutputSuccess(): void
    {
        $this->helper
            ->withPadding(5)
            ->withStyle('info.bg')
            ->output(['All done']);
        $expected = [
            '',
            '<info.bg>                  </info.bg>',
            '<info.bg>     All done     </info.bg>',
            '<info.bg>                  </info.bg>',
            '',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test that width is respected
     */
    public function testOutputPadding(): void
    {
        $this->helper
            ->withPadding(1)
            ->withStyle('info.bg')
            ->output(['All done']);
        $expected = [
            '',
            '<info.bg>          </info.bg>',
            '<info.bg> All done </info.bg>',
            '<info.bg>          </info.bg>',
            '',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }

    /**
     * Test that width is respected
     */
    public function testOutputLongestLine(): void
    {
        $this->helper
            ->withPadding(1)
            ->withStyle('info.bg')
            ->output(['All done', 'This line is longer', 'tiny']);
        $expected = [
            '',
            '<info.bg>                     </info.bg>',
            '<info.bg> All done            </info.bg>',
            '<info.bg> This line is longer </info.bg>',
            '<info.bg> tiny                </info.bg>',
            '<info.bg>                     </info.bg>',
            '',
        ];
        $this->assertEquals($expected, $this->stub->messages());
    }
}
