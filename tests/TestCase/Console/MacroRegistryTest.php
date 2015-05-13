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
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\MacroRegistry;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class MacroRegistryTest
 *
 */
class MacroRegistryTest extends TestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);
        $this->macros = new MacroRegistry($io);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->macros);
        parent::tearDown();
    }

    /**
     * test triggering callbacks on loaded tasks
     *
     * @return void
     */
    public function testLoad()
    {
        $result = $this->macros->load('Simple');
        $this->assertInstanceOf('TestApp\Shell\Macro\SimpleMacro', $result);
        $this->assertInstanceOf('TestApp\Shell\Macro\SimpleMacro', $this->macros->Simple);

        $result = $this->macros->loaded();
        $this->assertEquals(['Simple'], $result, 'loaded() results are wrong.');
    }

    /**
     * test missingtask exception
     *
     * @expectedException \Cake\Console\Exception\MissingMacroException
     * @return void
     */
    public function testLoadMissingMacro()
    {
        $this->macros->load('ThisTaskShouldAlwaysBeMissing');
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias()
    {
        Plugin::load('TestPlugin');

        $result = $this->macros->load('SimpleAliased', ['className' => 'Simple']);
        $this->assertInstanceOf('TestApp\Shell\Macro\SimpleMacro', $result);
        $this->assertInstanceOf('TestApp\Shell\Macro\SimpleMacro', $this->macros->SimpleAliased);

        $result = $this->macros->loaded();
        $this->assertEquals(['SimpleAliased'], $result, 'loaded() results are wrong.');

        $result = $this->macros->load('SomeMacro', ['className' => 'TestPlugin.Example']);
        $this->assertInstanceOf('TestPlugin\Shell\Macro\ExampleMacro', $result);
        $this->assertInstanceOf('TestPlugin\Shell\Macro\ExampleMacro', $this->macros->SomeMacro);

        $result = $this->macros->loaded();
        $this->assertEquals(['SimpleAliased', 'SomeMacro'], $result, 'loaded() results are wrong.');
    }
}
