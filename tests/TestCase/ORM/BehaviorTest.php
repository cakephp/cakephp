<?php
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

use Cake\ORM\Behavior;
use Cake\TestSuite\TestCase;

/**
 * Test Stub.
 */
class TestBehavior extends Behavior
{

    /**
     * Test for event bindings.
     */
    public function beforeFind()
    {
    }

    /**
     * Test for event bindings.
     */
    public function beforeRules()
    {
    }

    /**
     * Test for event bindings.
     */
    public function afterRules()
    {
    }

    /**
     * Test for event bindings.
     */
    public function buildRules()
    {
    }

    /**
     * Test for event bindings.
     */
    public function afterSaveCommit()
    {
    }

    /**
     * Test for event bindings.
     */
    public function afterDeleteCommit()
    {
    }
}

/**
 * Test Stub.
 */
class Test2Behavior extends Behavior
{

    protected $_defaultConfig = [
        'implementedFinders' => [
            'foo' => 'findFoo',
        ],
        'implementedMethods' => [
            'doSomething' => 'doSomething',
        ]
    ];

    /**
     * Test for event bindings.
     */
    public function beforeFind()
    {
    }

    /**
     * Test finder
     */
    public function findFoo()
    {
    }

    /**
     * Test method
     */
    public function doSomething()
    {
    }
}

/**
 * Test3Behavior
 */
class Test3Behavior extends Behavior
{

    /**
     * Test for event bindings.
     */
    public function beforeFind()
    {
    }

    /**
     * Test finder
     */
    public function findFoo()
    {
    }

    /**
     * Test method
     */
    public function doSomething()
    {
    }

    /**
     * Test method to ensure it is ignored as a callable method.
     */
    public function verifyConfig()
    {
        return parent::verifyConfig();
    }

    /**
     * implementedEvents
     *
     * This class does pretend to implement beforeFind
     *
     * @return void
     */
    public function implementedEvents()
    {
        return ['Model.beforeFind' => 'beforeFind'];
    }

    /**
     * implementedFinders
     */
    public function implementedFinders()
    {
    }

    /**
     * implementedMethods
     */
    public function implementedMethods()
    {
    }

    /**
     * Expose protected method for testing
     *
     * Since this is public - it'll show up as callable which is a side-effect
     *
     * @return array
     */
    public function testReflectionCache()
    {
        return $this->_reflectionCache();
    }
}

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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $config = ['key' => 'value'];
        $behavior = new TestBehavior($table, $config);
        $this->assertEquals($config, $behavior->config());
    }

    /**
     * Test getting table instance.
     *
     * @return void
     */
    public function testGetTable()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();

        $behavior = new TestBehavior($table);
        $this->assertSame($table, $behavior->getTable());
    }

    public function testReflectionCache()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test3Behavior($table);
        $expected = [
            'finders' => [
                'foo' => 'findFoo'
            ],
            'methods' => [
                'doSomething' => 'doSomething',
                'testReflectionCache' => 'testReflectionCache'
            ]
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new TestBehavior($table, ['priority' => 10]);
        $expected = [
            'Model.beforeFind' => [
                'priority' => 10,
                'callable' => 'beforeFind'
            ],
            'Model.afterSaveCommit' => [
                'priority' => 10,
                'callable' => 'afterSaveCommit'
            ],
            'Model.beforeRules' => [
                'priority' => 10,
                'callable' => 'beforeRules'
            ],
            'Model.afterRules' => [
                'priority' => 10,
                'callable' => 'afterRules'
            ],
            'Model.buildRules' => [
                'priority' => 10,
                'callable' => 'buildRules'
            ],
            'Model.afterDeleteCommit' => [
                'priority' => 10,
                'callable' => 'afterDeleteCommit'
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table);
        $expected = [
            'doSomething' => 'doSomething'
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedMethods' => [
                'aliased' => 'doSomething'
            ]
        ]);
        $expected = [
            'aliased' => 'doSomething'
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedMethods' => []
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedFinders' => [
                'aliased' => 'findFoo'
            ]
        ]);
        $expected = [
            'aliased' => 'findFoo'
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedFinders' => []
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedFinders' => [
                'aliased' => 'findFoo'
            ]
        ]);
        $behavior->verifyConfig();
        $this->assertTrue(true, 'No exception thrown');
    }

    /**
     * testVerifyImplementedFindersInvalid
     *
     *
     * @return void
     */
    public function testVerifyImplementedFindersInvalid()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->expectExceptionMessage('The method findNotDefined is not callable on class Cake\Test\TestCase\ORM\Test2Behavior');
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedFinders' => [
                'aliased' => 'findNotDefined'
            ]
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
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table);
        $behavior = new Test2Behavior($table, [
            'implementedMethods' => [
                'aliased' => 'doSomething'
            ]
        ]);
        $behavior->verifyConfig();
        $this->assertTrue(true, 'No exception thrown');
    }

    /**
     * testVerifyImplementedMethodsInvalid
     *
     *
     * @return void
     */
    public function testVerifyImplementedMethodsInvalid()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->expectExceptionMessage('The method iDoNotExist is not callable on class Cake\Test\TestCase\ORM\Test2Behavior');
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $behavior = new Test2Behavior($table, [
            'implementedMethods' => [
                'aliased' => 'iDoNotExist'
            ]
        ]);
        $behavior->verifyConfig();
    }
}
