<?php
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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;
use TestApp\Command\DemoCommand;

/**
 * Test case for Console\Command
 */
class CommandTest extends TestCase
{
    /**
     * test orm locator is setup
     *
     * @return void
     */
    public function testConstructorSetsLocator()
    {
        $command = new Command();
        $result = $command->getTableLocator();
        $this->assertInstanceOf(TableLocator::class, $result);
    }

    /**
     * test loadModel is configured properly
     *
     * @return void
     */
    public function testConstructorLoadModel()
    {
        $command = new Command();
        $command->loadModel('Comments');
        $this->assertInstanceOf(Table::class, $command->Comments);
    }

    /**
     * Test name
     *
     * @return void
     */
    public function testSetName()
    {
        $command = new Command();
        $this->assertSame($command, $command->setName('routes show'));
        $this->assertSame('routes show', $command->getName());
    }

    /**
     * Test invalid name
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The name 'routes_show' is missing a space. Names should look like `cake routes`
     * @return void
     */
    public function testSetNameInvalid()
    {
        $command = new Command();
        $command->setName('routes_show');
    }

    /**
     * Test invalid name
     *
     * @expectedException InvalidArgumentException
     * @return void
     */
    public function testSetNameInvalidLeadingSpace()
    {
        $command = new Command();
        $command->setName(' routes_show');
    }

    /**
     * Test option parser fetching
     *
     * @return void
     */
    public function testGetOptionParser()
    {
        $command = new Command();
        $command->setName('cake routes show');
        $parser = $command->getOptionParser();
        $this->assertInstanceOf(ConsoleOptionParser::class, $parser);
        $this->assertSame('routes show', $parser->getCommand());
    }

    /**
     * Test option parser fetching
     *
     * @expectedException RuntimeException
     * @return void
     */
    public function testGetOptionParserInvalid()
    {
        $command = $this->getMockBuilder(Command::class)
            ->setMethods(['buildOptionParser'])
            ->getMock();
        $command->expects($this->once())
            ->method('buildOptionParser')
            ->will($this->returnValue(null));
        $command->getOptionParser();
    }

    /**
     * Test that initialize is called.
     *
     * @return void
     */
    public function testRunCallsInitialize()
    {
        $command = $this->getMockBuilder(Command::class)
            ->setMethods(['initialize'])
            ->getMock();
        $command->setName('cake example');
        $command->expects($this->once())->method('initialize');
        $command->run([], $this->getMockIo(new ConsoleOutput()));
    }

    /**
     * Test run() outputs help
     *
     * @return void
     */
    public function testRunOutputHelp()
    {
        $command = new Command();
        $command->setName('cake demo');
        $output = new ConsoleOutput();

        $this->assertSame(
            Command::CODE_SUCCESS,
            $command->run(['-h'], $this->getMockIo($output))
        );
        $messages = implode("\n", $output->messages());
        $this->assertNotContains('Demo', $messages);
        $this->assertContains('cake demo [-h]', $messages);
    }

    /**
     * Test run() outputs help
     *
     * @return void
     */
    public function testRunOutputHelpLongOption()
    {
        $command = new Command();
        $command->setName('cake demo');
        $output = new ConsoleOutput();

        $this->assertSame(
            Command::CODE_SUCCESS,
            $command->run(['--help'], $this->getMockIo($output))
        );
        $messages = implode("\n", $output->messages());
        $this->assertNotContains('Demo', $messages);
        $this->assertContains('cake demo [-h]', $messages);
    }

    /**
     * Test run() sets output level
     *
     * @return void
     */
    public function testRunVerboseOption()
    {
        $command = new DemoCommand();
        $command->setName('cake demo');
        $output = new ConsoleOutput();

        $this->assertNull($command->run(['--verbose'], $this->getMockIo($output)));
        $messages = implode("\n", $output->messages());
        $this->assertContains('Verbose!', $messages);
        $this->assertContains('Demo Command!', $messages);
        $this->assertContains('Quiet!', $messages);
        $this->assertNotContains('cake demo [-h]', $messages);
    }

    /**
     * Test run() sets output level
     *
     * @return void
     */
    public function testRunQuietOption()
    {
        $command = new DemoCommand();
        $command->setName('cake demo');
        $output = new ConsoleOutput();

        $this->assertNull($command->run(['--quiet'], $this->getMockIo($output)));
        $messages = implode("\n", $output->messages());
        $this->assertContains('Quiet!', $messages);
        $this->assertNotContains('Verbose!', $messages);
        $this->assertNotContains('Demo Command!', $messages);
    }

    /**
     * Test run() sets option parser failure
     *
     * @return void
     */
    public function testRunOptionParserFailure()
    {
        $command = $this->getMockBuilder(Command::class)
            ->setMethods(['getOptionParser'])
            ->getMock();
        $parser = new ConsoleOptionParser('cake example');
        $parser->addArgument('name', ['required' => true]);

        $command->method('getOptionParser')->will($this->returnValue($parser));

        $output = new ConsoleOutput();
        $result = $command->run([], $this->getMockIo($output));
        $this->assertSame(Command::CODE_ERROR, $result);

        $messages = implode("\n", $output->messages());
        $this->assertContains('Error: Missing required arguments. name is required', $messages);
    }

    /**
     * Test abort()
     *
     * @expectedException \Cake\Console\Exception\StopException
     * @expectedExceptionCode 1
     * @return void
     */
    public function testAbort()
    {
        $command = new Command();
        $command->abort();
    }

    /**
     * Test abort()
     *
     * @expectedException \Cake\Console\Exception\StopException
     * @expectedExceptionCode 99
     * @return void
     */
    public function testAbortCustomCode()
    {
        $command = new Command();
        $command->abort(99);
    }

    protected function getMockIo($output)
    {
        $io = $this->getMockBuilder(ConsoleIo::class)
            ->setConstructorArgs([$output, $output, null, null])
            ->setMethods(['in'])
            ->getMock();

        return $io;
    }
}
