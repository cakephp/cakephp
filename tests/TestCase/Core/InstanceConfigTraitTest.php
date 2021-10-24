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
namespace Cake\Test\TestCase\Core;

use Cake\TestSuite\TestCase;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use TestApp\Config\ReadOnlyTestInstanceConfig;
use TestApp\Config\TestInstanceConfig;

class InstanceConfigTraitTest extends TestCase
{
    /**
     * @var \TestApp\Config\TestInstanceConfig
     */
    protected $object;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->object = new TestInstanceConfig();
    }

    /**
     * testDefaultsAreSet
     */
    public function testDefaultsAreSet(): void
    {
        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                ],
            ],
            $this->object->getConfig(),
            'runtime config should match the defaults if not overridden'
        );
    }

    /**
     * testGetSimple
     */
    public function testGetSimple(): void
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
     */
    public function testGetDot(): void
    {
        $this->assertSame(
            'value',
            $this->object->getConfig('a.nested'),
            'should return the nested value only'
        );
    }

    /**
     * testGetDefault
     */
    public function testGetDefault(): void
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
     */
    public function testSetSimple(): void
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
     */
    public function testSetNested(): void
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
                'new' => ['foo' => 'bar'],
            ],
            $this->object->getConfig(),
            'updates should be merged with existing config'
        );
    }

    /**
     * testSetNested
     */
    public function testSetArray(): void
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
                'new' => ['foo' => 'bar'],
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
                'multiple' => 'different',
            ],
            $this->object->getConfig(),
            'updates should be merged with existing config'
        );
    }

    public function testGetConfigOrFail(): void
    {
        $this->object->setConfig(['foo' => 'bar']);
        $this->assertSame(
            'bar',
            $this->object->getConfigOrFail('foo'),
            'should return the same value just set'
        );
    }

    public function testGetConfigOrFailException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected configuration `foo` not found.');

        $this->object->getConfigOrFail('foo');
    }

    /**
     * test shallow merge
     */
    public function testConfigShallow(): void
    {
        $this->object->configShallow(['a' => ['new_nested' => true], 'new' => 'bar']);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['new_nested' => true],
                'new' => 'bar',
            ],
            $this->object->getConfig(),
            'When merging a scalar property will be overwritten with an array'
        );
    }

    /**
     * testSetClobber
     */
    public function testSetClobber(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot set a.nested.value');
        $this->object->setConfig(['a.nested.value' => 'not possible'], null, false);
        $this->object->getConfig();
    }

    /**
     * testMerge
     */
    public function testMerge(): void
    {
        $this->object->setConfig(['a' => ['nother' => 'value']]);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value',
                ],
            ],
            $this->object->getConfig(),
            'Merging should not delete untouched array values'
        );
    }

    /**
     * testMergeDotKey
     */
    public function testMergeDotKey(): void
    {
        $this->object->setConfig('a.nother', 'value');

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value',
                ],
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
                    'nextra' => 'value',
                ],
            ],
            $this->object->getConfig(),
            'Merging should not delete untouched array values'
        );
    }

    /**
     * testSetDefaultsMerge
     */
    public function testSetDefaultsMerge(): void
    {
        $this->object->setConfig(['a' => ['nother' => 'value']]);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value',
                ],
            ],
            $this->object->getConfig(),
            'First access should act like any subsequent access'
        );
    }

    /**
     * testSetDefaultsNoMerge
     */
    public function testSetDefaultsNoMerge(): void
    {
        $this->object->setConfig(['a' => ['nother' => 'value']], null, false);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nother' => 'value',
                ],
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
     */
    public function testSetMergeNoClobber(): void
    {
        $this->object->setConfig(['a.nested.value' => 'it is possible']);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => [
                        'value' => 'it is possible',
                    ],
                    'other' => 'value',
                ],
            ],
            $this->object->getConfig(),
            'When merging a scalar property will be overwritten with an array'
        );
    }

    /**
     * testReadOnlyConfig
     */
    public function testReadOnlyConfig(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This Instance is readonly');
        $object = new ReadOnlyTestInstanceConfig();

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'value', 'other' => 'value'],
            ],
            $object->getConfig(),
            'default config should be returned'
        );

        $object->setConfig('throw.me', 'an exception');
    }

    /**
     * testDeleteSimple
     */
    public function testDeleteSimple(): void
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
     */
    public function testDeleteNested(): void
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
                    'other' => 'value',
                ],
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
                'a' => [],
            ],
            $this->object->getConfig(),
            'deleted keys should not be present'
        );
    }

    /**
     * testDeleteArray
     */
    public function testDeleteArray(): void
    {
        $this->object->setConfig('a', null);
        $this->assertNull(
            $this->object->getConfig('a'),
            'should delete the existing value'
        );
        $this->assertSame(
            [
                'some' => 'string',
            ],
            $this->object->getConfig(),
            'deleted keys should not be present'
        );
    }

    /**
     * testDeleteClobber
     */
    public function testDeleteClobber(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot unset a.nested.value.whoops');
        $this->object->setConfig('a.nested.value.whoops', null);
    }
}
