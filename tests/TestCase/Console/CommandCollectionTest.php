<?php
namespace Cake\Test\Console;

use Cake\Console\CommandCollection;
use Cake\Shell\I18nShell;
use Cake\Shell\RoutesShell;
use Cake\TestSuite\TestCase;
use stdClass;

/**
 * Test case for the CommandCollection
 */
class CommandCollectionTest extends TestCase
{
    public function testConstructor()
    {
        $this->markTestIncomplete();
    }

    public function testConstructorInvalidClass()
    {
        $this->markTestIncomplete();
    }

    public function testConstructorInvalidFactory()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test basic add/get
     *
     * @return void
     */
    public function testAdd()
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->add('routes', RoutesShell::class));
        $this->assertTrue($collection->has('routes'));
        $this->assertSame(RoutesShell::class, $collection->get('routes'));
    }

    /**
     * Test that add() replaces.
     *
     * @return void
     */
    public function testAddReplace()
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->add('routes', RoutesShell::class));
        $this->assertSame($collection, $collection->add('routes', I18nShell::class));
        $this->assertTrue($collection->has('routes'));
        $this->assertSame(I18nShell::class, $collection->get('routes'));
    }

    /**
     * Test adding with instances
     *
     * @return void
     */
    public function testAddInstance()
    {
        $collection = new CommandCollection();
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $shell = new RoutesShell($io);
        $collection->add('routes', $shell);

        $this->assertTrue($collection->has('routes'));
        $this->assertSame($shell, $collection->get('routes'));
    }

    /**
     * Instances that are not shells should fail.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage 'routes' is not a subclass of Cake\Console\Shell
     */
    public function testAddInvalidInstance()
    {
        $collection = new CommandCollection();
        $shell = new stdClass();
        $collection->add('routes', $shell);
    }

    /**
     * Class names that are not shells should fail
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage 'routes' is not a subclass of Cake\Console\Shell
     */
    public function testInvalidShellClassName()
    {
        $collection = new CommandCollection();
        $collection->add('routes', stdClass::class);
    }

    public function testRemove()
    {
        $this->markTestIncomplete();
    }

    public function testRemoveUnknown()
    {
        $this->markTestIncomplete();
    }

    public function testGetIterator()
    {
        $this->markTestIncomplete();
    }
}
