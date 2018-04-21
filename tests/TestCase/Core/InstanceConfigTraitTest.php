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
namespace Cake\Test\TestCase\Core;

use Cake\Core\InstanceConfigTrait;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * TestInstanceConfig
 */
class TestInstanceConfig
{

    use InstanceConfigTrait;

    /**
     * _defaultConfig
     *
     * Some default config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'some' => 'string',
        'a' => [
            'nested' => 'value',
            'other' => 'value'
        ]
    ];
}

/**
 * ReadOnlyTestInstanceConfig
 */
class ReadOnlyTestInstanceConfig
{

    use InstanceConfigTrait;

    /**
     * _defaultConfig
     *
     * Some default config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'some' => 'string',
        'a' => [
            'nested' => 'value',
            'other' => 'value'
        ]
    ];

    /**
     * Example of how to prevent modifying config at run time
     *
     * @throws \Exception
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    protected function _configWrite($key, $value = null)
    {
        throw new Exception('This Instance is readonly');
    }
}

/**
 * InstanceConfigTraitTest
 */
class InstanceConfigTraitTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new TestInstanceConfig();
    }

    /**
     * testDefaultsAreSet
     *
     * @return void
     */
    public function testDefaultsAreSet()
    {
        $this->deprecated(function () {
            $this->assertSame(
                [
                    'some' => 'string',
                    'a' => [
                        'nested' => 'value',
                        'other' => 'value'
                    ]
                ],
                $this->object->getConfig(),
                'runtime config should match the defaults if not overridden'
            );
        });
    }

    /**
     * testGetSimple
     *
     * @return void
     */
    public function testGetSimple()
    {
        $this->assertSame(
            'string',
            $this->object->getConfig('some'),
            'should return the key value only'
        );

        $this->assertSame(
            ['nested' => 'value', 'other' => 'value'],
            $this->object->getConfig('a'),
            'should return the key value only'
        );
    }

    /**
     * testGetDot
     *
     * @return void
     */
    public function testGetDot()
    {
        $this->assertSame(
            'value',
            $this->object->getConfig('a.nested'),
            'should return the nested value only'
        );
    }

    /**
     * testGetDefault
     *
     * @return void
     */
    public function testGetDefault()
    {
        $this->assertSame(
            'default',
            $this->object->getConfig('nonexistent', 'default')
        );

        $this->assertSame(
            'my-default',
            $this->object->getConfig('nested.nonexistent', 'my-default')
        );
    }

    /**
     * testSetSimple
     *
     * @return void
     */
    public function testSetSimple()
    {
        $this->object->setConfig('foo', 'bar');
        $this->assertSame(
            'bar',
            $this->object->getConfig('foo'),
            'should return the same value just set'
        );

        $return = $this->object->setConfig('some', 'zum');
        $this->assertSame(
            'zum',
            $this->object->getConfig('some'),
            'should return the overwritten value'
        );
        $this->assertSame(
            $this->object,
            $return,
            'write operations should return the instance'
        );

        $this->assertSame(
            [
                'some' => 'zum',
                'a' => ['nested' => 'value', 'other' => 'value'],
                'foo' => 'bar',
            ],
            $this->object->getConfig(),
            'updates should be merged with existing config'
        );
    }

    /**
     * testSetNested
     *
     * @return void
     */
    public function testSetNested()
    {
        $this->object->setConfig('new.foo', 'bar');
        $this->assertSame(
            'bar',
            $this->object->getConfig('new.foo'),
            'should return the same value just set'
        );

        $this->object->setConfig('a.nested', 'zum');
        $this->assertSame(
            'zum',
            $this->object->getConfig('a.nested'),
            'should return the overwritten value'
        );

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'zum', 'other' => 'value'],
                'new' => ['foo' => 'bar']
            ],
            $this->object->getConfig(),
            'updates should be merged with existing config'
        );
    }

    /**
     * testSetNested
     *
     * @return void
     */
    public function testSetArray()
    {
        $this->object->setConfig(['foo' => 'bar']);
        $this->assertSame(
            'bar',
            $this->object->getConfig('foo'),
            'should return the same value just set'
        );

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'value', 'other' => 'value'],
                'foo' => 'bar',
            ],
            $this->object->getConfig(),
            'updates should be merged with existing config'
        );

        $this->object->setConfig(['new.foo' => 'bar']);
        $this->assertSame(
            'bar',
            $this->object->getConfig('new.foo'),
            'should return the same value just set'
        );

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'value', 'other' => 'value'],
                'foo' => 'bar',
                'new' => ['foo' => 'bar']
            ],
            $this->object->getConfig(),
            'updates should be merged with existing config'
        );

        $this->object->setConfig(['multiple' => 'different', 'a.values.to' => 'set']);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'value', 'other' => 'value', 'values' => ['to' => 'set']],
                'foo' => 'bar',
                'new' => ['foo' => 'bar'],
                'multiple' => 'different'
            ],
            $this->object->getConfig(),
            'updates should be merged with existing config'
        );
    }

    /**
     * test shallow merge
     *
     * @return void
     */
    public function testConfigShallow()
    {
        $this->object->configShallow(['a' => ['new_nested' => true], 'new' => 'bar']);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['new_nested' => true],
                'new' => 'bar'
            ],
            $this->object->getConfig(),
            'When merging a scalar property will be overwritten with an array'
        );
    }

    /**
     * testSetClobber
     *
     * @return void
     */
    public function testSetClobber()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot set a.nested.value');
        $this->object->setConfig(['a.nested.value' => 'not possible'], null, false);
        $this->object->getConfig();
    }

    /**
     * testMerge
     *
     * @return void
     */
    public function testMerge()
    {
        $this->object->setConfig(['a' => ['nother' => 'value']]);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value'
                ]
            ],
            $this->object->getConfig(),
            'Merging should not delete untouched array values'
        );
    }

    /**
     * testMergeDotKey
     *
     * @return void
     */
    public function testMergeDotKey()
    {
        $this->object->setConfig('a.nother', 'value');

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value'
                ]
            ],
            $this->object->getConfig(),
            'Should act the same as having passed the equivalent array to the config function'
        );

        $this->object->setConfig(['a.nextra' => 'value']);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value',
                    'nextra' => 'value'
                ]
            ],
            $this->object->getConfig(),
            'Merging should not delete untouched array values'
        );
    }

    /**
     * testSetDefaultsMerge
     *
     * @return void
     */
    public function testSetDefaultsMerge()
    {
        $this->object->setConfig(['a' => ['nother' => 'value']]);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value'
                ]
            ],
            $this->object->getConfig(),
            'First access should act like any subsequent access'
        );
    }

    /**
     * testSetDefaultsNoMerge
     *
     * @return void
     */
    public function testSetDefaultsNoMerge()
    {
        $this->object->setConfig(['a' => ['nother' => 'value']], null, false);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nother' => 'value'
                ]
            ],
            $this->object->getConfig(),
            'If explicitly no-merge, array values should be overwritten'
        );
    }

    /**
     * testSetMergeNoClobber
     *
     * Merging offers no such protection of clobbering a value whilst implemented
     * using the Hash class
     *
     * @return void
     */
    public function testSetMergeNoClobber()
    {
        $this->object->setConfig(['a.nested.value' => 'it is possible']);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => [
                        'value' => 'it is possible'
                    ],
                    'other' => 'value'
                ]
            ],
            $this->object->getConfig(),
            'When merging a scalar property will be overwritten with an array'
        );
    }

    /**
     * testReadOnlyConfig
     *
     * @return void
     */
    public function testReadOnlyConfig()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This Instance is readonly');
        $object = new ReadOnlyTestInstanceConfig();

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'value', 'other' => 'value']
            ],
            $object->getConfig(),
            'default config should be returned'
        );

        $object->setConfig('throw.me', 'an exception');
    }

    /**
     * testDeleteSimple
     *
     * @return void
     */
    public function testDeleteSimple()
    {
        $this->object->setConfig('foo', null);
        $this->assertNull(
            $this->object->getConfig('foo'),
            'setting a new key to null should have no effect'
        );

        $this->object->setConfig('some', null);
        $this->assertNull(
            $this->object->getConfig('some'),
            'should delete the existing value'
        );

        $this->assertSame(
            [
                'a' => ['nested' => 'value', 'other' => 'value'],
            ],
            $this->object->getConfig(),
            'deleted keys should not be present'
        );
    }

    /**
     * testDeleteNested
     *
     * @return void
     */
    public function testDeleteNested()
    {
        $this->object->setConfig('new.foo', null);
        $this->assertNull(
            $this->object->getConfig('new.foo'),
            'setting a new key to null should have no effect'
        );

        $this->object->setConfig('a.nested', null);
        $this->assertNull(
            $this->object->getConfig('a.nested'),
            'should delete the existing value'
        );
        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'other' => 'value'
                ]
            ],
            $this->object->getConfig(),
            'deleted keys should not be present'
        );

        $this->object->setConfig('a.other', null);
        $this->assertNull(
            $this->object->getConfig('a.other'),
            'should delete the existing value'
        );
        $this->assertSame(
            [
                'some' => 'string',
                'a' => []
            ],
            $this->object->getConfig(),
            'deleted keys should not be present'
        );
    }

    /**
     * testDeleteArray
     *
     * @return void
     */
    public function testDeleteArray()
    {
        $this->object->setConfig('a', null);
        $this->assertNull(
            $this->object->getConfig('a'),
            'should delete the existing value'
        );
        $this->assertSame(
            [
                'some' => 'string'
            ],
            $this->object->getConfig(),
            'deleted keys should not be present'
        );
    }

    /**
     * testDeleteClobber
     *
     * @return void
     */
    public function testDeleteClobber()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot unset a.nested.value.whoops');
        $this->object->setConfig('a.nested.value.whoops', null);
    }
}
