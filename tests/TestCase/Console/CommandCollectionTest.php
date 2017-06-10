<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
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
    /**
     * Test constructor with valid classnames
     *
     * @return void
     */
    public function testConstructor()
    {
        $collection = new CommandCollection([
            'i18n' => I18nShell::class,
            'routes' => RoutesShell::class
        ]);
        $this->assertTrue($collection->has('routes'));
        $this->assertTrue($collection->has('i18n'));
        $this->assertCount(2, $collection);
    }

    /**
     * Constructor with invalid class names should blow up
     *
     * @return void
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage 'nope' is not a subclass of Cake\Console\Shell
     */
    public function testConstructorInvalidClass()
    {
        new CommandCollection([
            'i18n' => I18nShell::class,
            'nope' => stdClass::class
        ]);
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

    /**
     * Test removing a command
     *
     * @return void
     */
    public function testRemove()
    {
        $collection = new CommandCollection();
        $collection->add('routes', RoutesShell::class);
        $this->assertSame($collection, $collection->remove('routes'));
        $this->assertFalse($collection->has('routes'));
    }

    /**
     * Removing an unknown command does not fail
     *
     * @return void
     */
    public function testRemoveUnknown()
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->remove('nope'));
        $this->assertFalse($collection->has('nope'));
    }

    /**
     * test getIterator
     *
     * @return void
     */
    public function testGetIterator()
    {
        $in = [
            'i18n' => I18nShell::class,
            'routes' => RoutesShell::class
        ];
        $collection = new CommandCollection($in);
        $out = [];
        foreach ($collection as $key => $value) {
            $out[$key] = $value;
        }
        $this->assertEquals($in, $out);
    }
}
