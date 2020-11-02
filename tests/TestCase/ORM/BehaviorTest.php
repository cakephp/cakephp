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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Exception\CakeException;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use TestApp\Model\Behavior\Test2Behavior;
use TestApp\Model\Behavior\Test3Behavior;
use TestApp\Model\Behavior\TestBehavior;

/**
 * Behavior test case
 */
class BehaviorTest extends TestCase
{
    /**
     * Test the side effects of the constructor.
     *
     * @return void
     */
    public function testConstructor()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $config = ['key' => 'value'];
        $behavior = new TestBehavior($table, $config);
        $this->assertEquals($config, $behavior->getConfig());
    }

    /**
     * Test getting table instance.
     *
     * @return void
     */
    public function testGetTable()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();

        $behavior = new TestBehavior($table);
        $this->assertSame($table, $behavior->table());
    }

    public function testReflectionCache()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test3Behavior($table);
        $expected = [
            'finders' => [
                'foo' => 'findFoo',
            ],
            'methods' => [
                'doSomething' => 'doSomething',
                'testReflectionCache' => 'testReflectionCache',
            ],
        ];
        $this->assertEquals($expected, $behavior->testReflectionCache());
    }

    /**
     * Test the default behavior of implementedEvents
     *
     * @return void
     */
    public function testImplementedEvents()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new TestBehavior($table);
        $expected = [
            'Model.beforeFind' => 'beforeFind',
            'Model.afterSaveCommit' => 'afterSaveCommit',
            'Model.buildRules' => 'buildRules',
            'Model.beforeRules' => 'beforeRules',
            'Model.afterRules' => 'afterRules',
            'Model.afterDeleteCommit' => 'afterDeleteCommit',
        ];
        $this->assertEquals($expected, $behavior->implementedEvents());
    }

    /**
     * Test that implementedEvents uses the priority setting.
     *
     * @return void
     */
    public function testImplementedEventsWithPriority()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new TestBehavior($table, ['priority' => 10]);
        $expected = [
            'Model.beforeFind' => [
                'priority' => 10,
                'callable' => 'beforeFind',
            ],
            'Model.afterSaveCommit' => [
                'priority' => 10,
                'callable' => 'afterSaveCommit',
            ],
            'Model.beforeRules' => [
                'priority' => 10,
                'callable' => 'beforeRules',
            ],
            'Model.afterRules' => [
                'priority' => 10,
                'callable' => 'afterRules',
            ],
            'Model.buildRules' => [
                'priority' => 10,
                'callable' => 'buildRules',
            ],
            'Model.afterDeleteCommit' => [
                'priority' => 10,
                'callable' => 'afterDeleteCommit',
            ],
        ];
        $this->assertEquals($expected, $behavior->implementedEvents());
    }

    /**
     * testImplementedMethods
     *
     * @return void
     */
    public function testImplementedMethods()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table);
        $expected = [
            'doSomething' => 'doSomething',
        ];
        $this->assertEquals($expected, $behavior->implementedMethods());
    }

    /**
     * testImplementedMethodsAliased
     *
     * @return void
     */
    public function testImplementedMethodsAliased()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedMethods' => [
                'aliased' => 'doSomething',
            ],
        ]);
        $expected = [
            'aliased' => 'doSomething',
        ];
        $this->assertEquals($expected, $behavior->implementedMethods());
    }

    /**
     * testImplementedMethodsDisabled
     *
     * @return void
     */
    public function testImplementedMethodsDisabled()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedMethods' => [],
        ]);
        $expected = [];
        $this->assertEquals($expected, $behavior->implementedMethods());
    }

    /**
     * testImplementedFinders
     *
     * @return void
     */
    public function testImplementedFinders()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table);
        $expected = [
            'foo' => 'findFoo',
        ];
        $this->assertEquals($expected, $behavior->implementedFinders());
    }

    /**
     * testImplementedFindersAliased
     *
     * @return void
     */
    public function testImplementedFindersAliased()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedFinders' => [
                'aliased' => 'findFoo',
            ],
        ]);
        $expected = [
            'aliased' => 'findFoo',
        ];
        $this->assertEquals($expected, $behavior->implementedFinders());
    }

    /**
     * testImplementedFindersDisabled
     *
     * @return void
     */
    public function testImplementedFindersDisabled()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedFinders' => [],
        ]);
        $this->assertEquals([], $behavior->implementedFinders());
    }

    /**
     * testVerifyConfig
     *
     * Don't expect an exception to be thrown
     *
     * @return void
     */
    public function testVerifyConfig()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table);
        $behavior->verifyConfig();
        $this->assertTrue(true, 'No exception thrown');
    }

    /**
     * testVerifyConfigImplementedFindersOverridden
     *
     * Simply don't expect an exception to be thrown
     *
     * @return void
     */
    public function testVerifyConfigImplementedFindersOverridden()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedFinders' => [
                'aliased' => 'findFoo',
            ],
        ]);
        $behavior->verifyConfig();
        $this->assertTrue(true, 'No exception thrown');
    }

    /**
     * testVerifyImplementedFindersInvalid
     *
     * @return void
     */
    public function testVerifyImplementedFindersInvalid()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The method findNotDefined is not callable on class ' . Test2Behavior::class);
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedFinders' => [
                'aliased' => 'findNotDefined',
            ],
        ]);
        $behavior->verifyConfig();
    }

    /**
     * testVerifyConfigImplementedMethodsOverridden
     *
     * Don't expect an exception to be thrown
     *
     * @return void
     */
    public function testVerifyConfigImplementedMethodsOverridden()
    {
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table);
        $behavior = new Test2Behavior($table, [
            'implementedMethods' => [
                'aliased' => 'doSomething',
            ],
        ]);
        $behavior->verifyConfig();
        $this->assertTrue(true, 'No exception thrown');
    }

    /**
     * testVerifyImplementedMethodsInvalid
     *
     * @return void
     */
    public function testVerifyImplementedMethodsInvalid()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The method iDoNotExist is not callable on class ' . Test2Behavior::class);
        $table = $this->getMockBuilder(Table::class)->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedMethods' => [
                'aliased' => 'iDoNotExist',
            ],
        ]);
        $behavior->verifyConfig();
    }
}
