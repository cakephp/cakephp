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
use TestApp\Config\TestInstanceConfigUnderscore;

class InstanceConfigTraitTest extends TestCase
{
    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function configProvider(): array
    {
        return [
            'config' => [new TestInstanceConfig()],
            'underscore' => [new TestInstanceConfigUnderscore()],
        ];
    }

    /**
     * @dataProvider configProvider
     */
    public function testDefaultsAreSet($config): void
    {
        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                ],
            ],
            $config->getConfig(),
            'runtime config should match the defaults if not overridden'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testGetSimple($config): void
    {
        $this->assertSame(
            'string',
            $config->getConfig('some'),
            'should return the key value only'
        );

        $this->assertSame(
            ['nested' => 'value', 'other' => 'value'],
            $config->getConfig('a'),
            'should return the key value only'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testGetDot($config): void
    {
        $this->assertSame(
            'value',
            $config->getConfig('a.nested'),
            'should return the nested value only'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testGetDefault($config): void
    {
        $this->assertSame(
            'default',
            $config->getConfig('nonexistent', 'default')
        );

        $this->assertSame(
            'my-default',
            $config->getConfig('nested.nonexistent', 'my-default')
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testSetSimple($config): void
    {
        $config->setConfig('foo', 'bar');
        $this->assertSame(
            'bar',
            $config->getConfig('foo'),
            'should return the same value just set'
        );

        $return = $config->setConfig('some', 'zum');
        $this->assertSame(
            'zum',
            $config->getConfig('some'),
            'should return the overwritten value'
        );
        $this->assertSame(
            $config,
            $return,
            'write operations should return the instance'
        );

        $this->assertSame(
            [
                'some' => 'zum',
                'a' => ['nested' => 'value', 'other' => 'value'],
                'foo' => 'bar',
            ],
            $config->getConfig(),
            'updates should be merged with existing config'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testSetNested($config): void
    {
        $config->setConfig('new.foo', 'bar');
        $this->assertSame(
            'bar',
            $config->getConfig('new.foo'),
            'should return the same value just set'
        );

        $config->setConfig('a.nested', 'zum');
        $this->assertSame(
            'zum',
            $config->getConfig('a.nested'),
            'should return the overwritten value'
        );

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'zum', 'other' => 'value'],
                'new' => ['foo' => 'bar'],
            ],
            $config->getConfig(),
            'updates should be merged with existing config'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testSetArray($config): void
    {
        $config->setConfig(['foo' => 'bar']);
        $this->assertSame(
            'bar',
            $config->getConfig('foo'),
            'should return the same value just set'
        );

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'value', 'other' => 'value'],
                'foo' => 'bar',
            ],
            $config->getConfig(),
            'updates should be merged with existing config'
        );

        $config->setConfig(['new.foo' => 'bar']);
        $this->assertSame(
            'bar',
            $config->getConfig('new.foo'),
            'should return the same value just set'
        );

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'value', 'other' => 'value'],
                'foo' => 'bar',
                'new' => ['foo' => 'bar'],
            ],
            $config->getConfig(),
            'updates should be merged with existing config'
        );

        $config->setConfig(['multiple' => 'different', 'a.values.to' => 'set']);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['nested' => 'value', 'other' => 'value', 'values' => ['to' => 'set']],
                'foo' => 'bar',
                'new' => ['foo' => 'bar'],
                'multiple' => 'different',
            ],
            $config->getConfig(),
            'updates should be merged with existing config'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testGetConfigOrFail($config): void
    {
        $config->setConfig(['foo' => 'bar']);
        $this->assertSame(
            'bar',
            $config->getConfigOrFail('foo'),
            'should return the same value just set'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testGetConfigOrFailException($config): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected configuration `foo` not found.');

        $config->getConfigOrFail('foo');
    }

    /**
     * @dataProvider configProvider
     */
    public function testConfigShallow($config): void
    {
        $config->configShallow(['a' => ['new_nested' => true], 'new' => 'bar']);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => ['new_nested' => true],
                'new' => 'bar',
            ],
            $config->getConfig(),
            'When merging a scalar property will be overwritten with an array'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testSetClobber($config): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot set a.nested.value');
        $config->setConfig(['a.nested.value' => 'not possible'], null, false);
        $config->getConfig();
    }

    /**
     * @dataProvider configProvider
     */
    public function testMerge($config): void
    {
        $config->setConfig(['a' => ['nother' => 'value']]);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value',
                ],
            ],
            $config->getConfig(),
            'Merging should not delete untouched array values'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testMergeDotKey($config): void
    {
        $config->setConfig('a.nother', 'value');

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value',
                ],
            ],
            $config->getConfig(),
            'Should act the same as having passed the equivalent array to the config function'
        );

        $config->setConfig(['a.nextra' => 'value']);

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
            $config->getConfig(),
            'Merging should not delete untouched array values'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testSetDefaultsMerge($config): void
    {
        $config->setConfig(['a' => ['nother' => 'value']]);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nested' => 'value',
                    'other' => 'value',
                    'nother' => 'value',
                ],
            ],
            $config->getConfig(),
            'First access should act like any subsequent access'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testSetDefaultsNoMerge($config): void
    {
        $config->setConfig(['a' => ['nother' => 'value']], null, false);

        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'nother' => 'value',
                ],
            ],
            $config->getConfig(),
            'If explicitly no-merge, array values should be overwritten'
        );
    }

    /**
     * testSetMergeNoClobber
     *
     * Merging offers no such protection of clobbering a value whilst implemented
     * using the Hash class
     *
     * @dataProvider configProvider
     */
    public function testSetMergeNoClobber($config): void
    {
        $config->setConfig(['a.nested.value' => 'it is possible']);

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
            $config->getConfig(),
            'When merging a scalar property will be overwritten with an array'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testReadOnlyConfig($config): void
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
     * @dataProvider configProvider
     */
    public function testDeleteSimple($config): void
    {
        $config->setConfig('foo', null);
        $this->assertNull(
            $config->getConfig('foo'),
            'setting a new key to null should have no effect'
        );

        $config->setConfig('some', null);
        $this->assertNull(
            $config->getConfig('some'),
            'should delete the existing value'
        );

        $this->assertSame(
            [
                'a' => ['nested' => 'value', 'other' => 'value'],
            ],
            $config->getConfig(),
            'deleted keys should not be present'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testDeleteNested($config): void
    {
        $config->setConfig('new.foo', null);
        $this->assertNull(
            $config->getConfig('new.foo'),
            'setting a new key to null should have no effect'
        );

        $config->setConfig('a.nested', null);
        $this->assertNull(
            $config->getConfig('a.nested'),
            'should delete the existing value'
        );
        $this->assertSame(
            [
                'some' => 'string',
                'a' => [
                    'other' => 'value',
                ],
            ],
            $config->getConfig(),
            'deleted keys should not be present'
        );

        $config->setConfig('a.other', null);
        $this->assertNull(
            $config->getConfig('a.other'),
            'should delete the existing value'
        );
        $this->assertSame(
            [
                'some' => 'string',
                'a' => [],
            ],
            $config->getConfig(),
            'deleted keys should not be present'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testDeleteArray($config): void
    {
        $config->setConfig('a', null);
        $this->assertNull(
            $config->getConfig('a'),
            'should delete the existing value'
        );
        $this->assertSame(
            [
                'some' => 'string',
            ],
            $config->getConfig(),
            'deleted keys should not be present'
        );
    }

    /**
     * @dataProvider configProvider
     */
    public function testDeleteClobber($config): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot unset a.nested.value.whoops');
        $config->setConfig('a.nested.value.whoops', null);
    }
}
