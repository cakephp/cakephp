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
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use ArrayObject;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use InvalidArgumentException;
use RuntimeException;
use stdClass;

/**
 * HashTest
 */
class HashTest extends TestCase
{
    /**
     * Data provider
     *
     * @return array
     */
    public static function articleData(): array
    {
        return [
            [
                'Article' => [
                    'id' => '1',
                    'user_id' => '1',
                    'title' => 'First Article',
                    'body' => 'First Article Body',
                ],
                'User' => [
                    'id' => '1',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                ],
                'Comment' => [
                    [
                        'id' => '1',
                        'article_id' => '1',
                        'user_id' => '2',
                        'comment' => 'First Comment for First Article',
                    ],
                    [
                        'id' => '2',
                        'article_id' => '1',
                        'user_id' => '4',
                        'comment' => 'Second Comment for First Article',
                    ],
                ],
                'Tag' => [
                    [
                        'id' => '1',
                        'tag' => 'tag1',
                    ],
                    [
                        'id' => '2',
                        'tag' => 'tag2',
                    ],
                ],
                'Deep' => [
                    'Nesting' => [
                        'test' => [
                            1 => 'foo',
                            2 => [
                                'and' => ['more' => 'stuff'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'Article' => [
                    'id' => '2',
                    'user_id' => '1',
                    'title' => 'Second Article',
                    'body' => 'Second Article Body',
                    'published' => 'Y',
                ],
                'User' => [
                    'id' => '2',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                ],
                'Comment' => [],
                'Tag' => [],
            ],
            [
                'Article' => [
                    'id' => '3',
                    'user_id' => '1',
                    'title' => 'Third Article',
                    'body' => 'Third Article Body',
                ],
                'User' => [
                    'id' => '3',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                ],
                'Comment' => [],
                'Tag' => [],
            ],
            [
                'Article' => [
                    'id' => '4',
                    'user_id' => '1',
                    'title' => 'Fourth Article',
                    'body' => 'Fourth Article Body',
                ],
                'User' => [
                    'id' => '4',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                ],
                'Comment' => [],
                'Tag' => [],
            ],
            [
                'Article' => [
                    'id' => '5',
                    'user_id' => '1',
                    'title' => 'Fifth Article',
                    'body' => 'Fifth Article Body',
                ],
                'User' => [
                    'id' => '5',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                    ],
                'Comment' => [],
                'Tag' => [],
            ],
        ];
    }

    /**
     * Data provider
     */
    public static function articleDataObject(): ArrayObject
    {
        return new ArrayObject([
            new Entity([
                'Article' => new ArrayObject([
                    'id' => '1',
                    'user_id' => '1',
                    'title' => 'First Article',
                    'body' => 'First Article Body',
                ]),
                'User' => new ArrayObject([
                    'id' => '1',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                ]),
                'Comment' => new ArrayObject([
                    new ArrayObject([
                        'id' => '1',
                        'article_id' => '1',
                        'user_id' => '2',
                        'comment' => 'First Comment for First Article',
                    ]),
                    new ArrayObject([
                        'id' => '2',
                        'article_id' => '1',
                        'user_id' => '4',
                        'comment' => 'Second Comment for First Article',
                    ]),
                ]),
                'Tag' => new ArrayObject([
                    new ArrayObject([
                        'id' => '1',
                        'tag' => 'tag1',
                    ]),
                    new ArrayObject([
                        'id' => '2',
                        'tag' => 'tag2',
                    ]),
                ]),
                'Deep' => new ArrayObject([
                    'Nesting' => new ArrayObject([
                        'test' => new ArrayObject([
                            1 => 'foo',
                            2 => new ArrayObject([
                                'and' => new ArrayObject(['more' => 'stuff']),
                            ]),
                        ]),
                    ]),
                ]),
            ]),
            new ArrayObject([
                'Article' => new ArrayObject([
                    'id' => '2',
                    'user_id' => '1',
                    'title' => 'Second Article',
                    'body' => 'Second Article Body',
                    'published' => 'Y',
                ]),
                'User' => new ArrayObject([
                    'id' => '2',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                ]),
                'Comment' => new ArrayObject([]),
                'Tag' => new ArrayObject([]),
            ]),
            new ArrayObject([
                'Article' => new ArrayObject([
                    'id' => '3',
                    'user_id' => '1',
                    'title' => 'Third Article',
                    'body' => 'Third Article Body',
                ]),
                'User' => new ArrayObject([
                    'id' => '3',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                ]),
                'Comment' => new ArrayObject([]),
                'Tag' => new ArrayObject([]),
            ]),
            new ArrayObject([
                'Article' => new ArrayObject([
                    'id' => '4',
                    'user_id' => '1',
                    'title' => 'Fourth Article',
                    'body' => 'Fourth Article Body',
                ]),
                'User' => new ArrayObject([
                    'id' => '4',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                ]),
                'Comment' => new ArrayObject([]),
                'Tag' => new ArrayObject([]),
            ]),
            new ArrayObject([
                'Article' => new ArrayObject([
                    'id' => '5',
                    'user_id' => '1',
                    'title' => 'Fifth Article',
                    'body' => 'Fifth Article Body',
                ]),
                'User' => new ArrayObject([
                    'id' => '5',
                    'user' => 'mariano',
                    'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                    ]),
                'Comment' => new ArrayObject([]),
                'Tag' => new ArrayObject([]),
            ]),
        ]);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function articleDataSets(): array
    {
        return [
            [static::articleData()],
            [static::articleDataObject()],
        ];
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function userData(): array
    {
        return [
            [
                'User' => [
                    'id' => 2,
                    'group_id' => 1,
                    'Data' => [
                        'user' => 'mariano.iglesias',
                        'name' => 'Mariano Iglesias',
                    ],
                ],
            ],
            [
                'User' => [
                    'id' => 14,
                    'group_id' => 2,
                    'Data' => [
                        'user' => 'phpnut',
                        'name' => 'Larry E. Masters',
                    ],
                ],
            ],
            [
                'User' => [
                    'id' => 25,
                    'group_id' => 1,
                    'Data' => [
                        'user' => 'gwoo',
                        'name' => 'The Gwoo',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test get()
     */
    public function testGet(): void
    {
        $data = ['abc', 'def'];

        $result = Hash::get($data, '0');
        $this->assertSame('abc', $result);

        $result = Hash::get($data, 0);
        $this->assertSame('abc', $result);

        $result = Hash::get($data, '1');
        $this->assertSame('def', $result);

        $data = self::articleData();

        $result = Hash::get([], '1.Article.title');
        $this->assertNull($result);

        $result = Hash::get($data, '');
        $this->assertNull($result);

        $result = Hash::get($data, null, '-');
        $this->assertSame('-', $result);

        $result = Hash::get($data, '0.Article.title');
        $this->assertSame('First Article', $result);

        $result = Hash::get($data, '1.Article.title');
        $this->assertSame('Second Article', $result);

        $result = Hash::get($data, '5.Article.title');
        $this->assertNull($result);

        $default = ['empty'];
        $this->assertSame($default, Hash::get($data, '5.Article.title', $default));
        $this->assertSame($default, Hash::get([], '5.Article.title', $default));

        $result = Hash::get($data, '1.Article.title.not_there');
        $this->assertNull($result);

        $result = Hash::get($data, '1.Article');
        $this->assertSame($data[1]['Article'], $result);

        $result = Hash::get($data, ['1', 'Article']);
        $this->assertSame($data[1]['Article'], $result);

        // Object which implements ArrayAccess
        $nested = new ArrayObject([
            'user' => 'bar',
        ]);
        $data = new ArrayObject([
            'name' => 'foo',
            'associated' => $nested,
        ]);

        $return = Hash::get($data, 'name');
        $this->assertSame('foo', $return);

        $return = Hash::get($data, 'associated');
        $this->assertSame($nested, $return);

        $return = Hash::get($data, 'associated.user');
        $this->assertSame('bar', $return);

        $return = Hash::get($data, 'nonexistent');
        $this->assertNull($return);

        $data = ['a' => ['b' => ['c' => ['d' => 1]]]];
        $this->assertSame(1, Hash::get(new ArrayObject($data), 'a.b.c.d'));
    }

    /**
     * Test that get() can extract '' key data.
     */
    public function testGetEmptyKey(): void
    {
        $data = [
            '' => 'some value',
        ];
        $result = Hash::get($data, '');
        $this->assertSame($data[''], $result);
    }

    /**
     * Test get() for invalid $data type
     */
    public function testGetInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type, must be an array or \ArrayAccess instance.');
        Hash::get('string', 'path');
    }

    /**
     * Test get() with an invalid path
     */
    public function testGetInvalidPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Hash::get(['one' => 'two'], true);
    }

    /**
     * Test dimensions.
     */
    public function testDimensions(): void
    {
        $result = Hash::dimensions([]);
        $this->assertSame($result, 0);

        $data = ['one', '2', 'three'];
        $result = Hash::dimensions($data);
        $this->assertSame($result, 1);

        $data = ['1' => '1.1', '2', '3'];
        $result = Hash::dimensions($data);
        $this->assertSame($result, 1);

        $data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => '3.1.1']];
        $result = Hash::dimensions($data);
        $this->assertSame($result, 2);

        $data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
        $result = Hash::dimensions($data);
        $this->assertSame($result, 1);

        $data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
        $result = Hash::dimensions($data);
        $this->assertSame($result, 2);
    }

    /**
     * Test maxDimensions
     */
    public function testMaxDimensions(): void
    {
        $data = [];
        $result = Hash::maxDimensions($data);
        $this->assertSame(0, $result);

        $data = ['a', 'b'];
        $result = Hash::maxDimensions($data);
        $this->assertSame(1, $result);

        $data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
        $result = Hash::maxDimensions($data);
        $this->assertSame(2, $result);

        $data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
        $result = Hash::maxDimensions($data);
        $this->assertSame(3, $result);

        $data = [
            '1' => ['1.1' => '1.1.1'],
            ['2' => ['2.1' => ['2.1.1' => '2.1.1.1']]],
            '3' => ['3.1' => ['3.1.1' => '3.1.1.1']],
        ];
        $result = Hash::maxDimensions($data);
        $this->assertSame(4, $result);

        $data = [
           '1' => [
               '1.1' => '1.1.1',
               '1.2' => [
                   '1.2.1' => [
                       '1.2.1.1',
                       ['1.2.2.1'],
                   ],
               ],
           ],
           '2' => ['2.1' => '2.1.1'],
        ];
        $result = Hash::maxDimensions($data);
        $this->assertSame(5, $result);

        $data = [
           '1' => false,
           '2' => ['2.1' => '2.1.1'],
        ];
        $result = Hash::maxDimensions($data);
        $this->assertSame(2, $result);
    }

    /**
     * Tests Hash::flatten
     */
    public function testFlatten(): void
    {
        $data = ['Larry', 'Curly', 'Moe'];
        $result = Hash::flatten($data);
        $this->assertSame($result, $data);

        $data[9] = 'Shemp';
        $result = Hash::flatten($data);
        $this->assertSame($result, $data);

        $data = [
            [
                'Post' => ['id' => '1', 'author_id' => '1', 'title' => 'First Post'],
                'Author' => ['id' => '1', 'user' => 'nate', 'password' => 'foo'],
            ],
            [
                'Post' => ['id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body'],
                'Author' => ['id' => '3', 'user' => 'larry', 'password' => null],
            ],
        ];
        $result = Hash::flatten($data);
        $expected = [
            '0.Post.id' => '1',
            '0.Post.author_id' => '1',
            '0.Post.title' => 'First Post',
            '0.Author.id' => '1',
            '0.Author.user' => 'nate',
            '0.Author.password' => 'foo',
            '1.Post.id' => '2',
            '1.Post.author_id' => '3',
            '1.Post.title' => 'Second Post',
            '1.Post.body' => 'Second Post Body',
            '1.Author.id' => '3',
            '1.Author.user' => 'larry',
            '1.Author.password' => null,
        ];
        $this->assertSame($expected, $result);

        $data = [
            [
                'Post' => ['id' => '1', 'author_id' => null, 'title' => 'First Post'],
                'Author' => [],
            ],
        ];
        $result = Hash::flatten($data);
        $expected = [
            '0.Post.id' => '1',
            '0.Post.author_id' => null,
            '0.Post.title' => 'First Post',
            '0.Author' => [],
        ];
        $this->assertSame($expected, $result);

        $data = [
            ['Post' => ['id' => 1]],
            ['Post' => ['id' => 2]],
        ];
        $result = Hash::flatten($data, '/');
        $expected = [
            '0/Post/id' => 1,
            '1/Post/id' => 2,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test diff();
     */
    public function testDiff(): void
    {
        $a = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about'],
        ];
        $b = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about'],
            2 => ['name' => 'contact'],
        ];

        $result = Hash::diff($a, []);
        $expected = $a;
        $this->assertSame($expected, $result);

        $result = Hash::diff([], $b);
        $expected = $b;
        $this->assertSame($expected, $result);

        $result = Hash::diff($a, $b);
        $expected = [
            2 => ['name' => 'contact'],
        ];
        $this->assertSame($expected, $result);

        $b = [
            0 => ['name' => 'me'],
            1 => ['name' => 'about'],
        ];

        $result = Hash::diff($a, $b);
        $expected = [
            0 => ['name' => 'main'],
        ];
        $this->assertSame($expected, $result);

        $a = [];
        $b = ['name' => 'bob', 'address' => 'home'];
        $result = Hash::diff($a, $b);
        $this->assertSame($result, $b);

        $a = ['name' => 'bob', 'address' => 'home'];
        $b = [];
        $result = Hash::diff($a, $b);
        $this->assertSame($result, $a);

        $a = ['key' => true, 'another' => false, 'name' => 'me'];
        $b = ['key' => 1, 'another' => 0];
        $expected = ['name' => 'me'];
        $result = Hash::diff($a, $b);
        $this->assertSame($expected, $result);

        $a = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $b = ['key' => 'differentValue', 'another' => null];
        $expected = ['key' => 'value', 'name' => 'me'];
        $result = Hash::diff($a, $b);
        $this->assertSame($expected, $result);

        $a = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $b = ['key' => 'differentValue', 'another' => 'value'];
        $expected = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $result = Hash::diff($a, $b);
        $this->assertSame($expected, $result);

        $a = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $b = ['key' => 'differentValue', 'another' => 'value'];
        $expected = ['key' => 'differentValue', 'another' => 'value', 'name' => 'me'];
        $result = Hash::diff($b, $a);
        $this->assertSame($expected, $result);

        $a = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $b = [0 => 'differentValue', 1 => 'value'];
        $expected = $a + $b;
        $result = Hash::diff($a, $b);
        $this->assertSame($expected, $result);
    }

    /**
     * Test merge()
     */
    public function testMerge(): void
    {
        $result = Hash::merge(['foo'], ['bar']);
        $this->assertSame($result, ['foo', 'bar']);

        $a = ['foo', 'foo2'];
        $b = ['bar', 'bar2'];
        $expected = ['foo', 'foo2', 'bar', 'bar2'];
        $this->assertSame($expected, Hash::merge($a, $b));

        $a = ['foo' => 'bar', 'bar' => 'foo'];
        $b = ['foo' => 'no-bar', 'bar' => 'no-foo'];
        $expected = ['foo' => 'no-bar', 'bar' => 'no-foo'];
        $this->assertSame($expected, Hash::merge($a, $b));

        $a = ['users' => ['bob', 'jim']];
        $b = ['users' => ['lisa', 'tina']];
        $expected = ['users' => ['bob', 'jim', 'lisa', 'tina']];
        $this->assertSame($expected, Hash::merge($a, $b));

        $a = ['users' => ['jim', 'bob']];
        $b = ['users' => 'none'];
        $expected = ['users' => 'none'];
        $this->assertSame($expected, Hash::merge($a, $b));

        $a = [
            'Tree',
            'CounterCache',
            'Upload' => [
                'folder' => 'products',
                'fields' => ['image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id'],
            ],
        ];
        $b = [
            'Cacheable' => ['enabled' => false],
            'Limit',
            'Bindable',
            'Validator',
            'Transactional',
        ];
        $expected = [
            'Tree',
            'CounterCache',
            'Upload' => [
                'folder' => 'products',
                'fields' => ['image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id'],
            ],
            'Cacheable' => ['enabled' => false],
            'Limit',
            'Bindable',
            'Validator',
            'Transactional',
        ];
        $this->assertSame($expected, Hash::merge($a, $b));
    }

    /**
     * Test that merge() works with variadic arguments.
     */
    public function testMergeVariadic(): void
    {
        $result = Hash::merge(
            ['hkuc' => ['lion']],
            ['hkuc' => 'lion']
        );
        $expected = ['hkuc' => 'lion'];
        $this->assertSame($expected, $result);

        $result = Hash::merge(
            ['hkuc' => ['lion']],
            ['hkuc' => ['lion']],
            ['hkuc' => 'lion']
        );
        $this->assertSame($expected, $result);

        $result = Hash::merge(['foo'], ['user' => 'bob', 'no-bar'], 'bar');
        $this->assertSame($result, ['foo', 'user' => 'bob', 'no-bar', 'bar']);

        $a = ['users' => ['lisa' => ['id' => 5, 'pw' => 'secret']], 'cakephp'];
        $b = ['users' => ['lisa' => ['pw' => 'new-pass', 'age' => 23]], 'ice-cream'];
        $expected = [
            'users' => ['lisa' => ['id' => 5, 'pw' => 'new-pass', 'age' => 23]],
            'cakephp',
            'ice-cream',
        ];
        $result = Hash::merge($a, $b);
        $this->assertSame($expected, $result);

        $c = [
            'users' => ['lisa' => ['pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog']],
            'chocolate',
        ];
        $expected = [
            'users' => ['lisa' => ['id' => 5, 'pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog']],
            'cakephp',
            'ice-cream',
            'chocolate',
        ];
        $this->assertSame($expected, Hash::merge($a, $b, $c));
        $this->assertSame($expected, Hash::merge($a, $b, [], $c));
    }

    /**
     * test normalizing arrays
     */
    public function testNormalize(): void
    {
        $result = Hash::normalize(['one', 'two', 'three']);
        $expected = ['one' => null, 'two' => null, 'three' => null];
        $this->assertSame($expected, $result);

        $result = Hash::normalize(['one', 'two', 'three'], false);
        $expected = ['one', 'two', 'three'];
        $this->assertSame($expected, $result);

        $result = Hash::normalize(['one', 'two', 'three'], false, []);
        $expected = ['one', 'two', 'three'];
        $this->assertSame($expected, $result);

        $result = Hash::normalize(['one' => 1, 'two' => 2, 'three' => 3, 'four'], false);
        $expected = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => null];
        $this->assertSame($expected, $result);

        $result = Hash::normalize(['one' => 1, 'two' => 2, 'three' => 3, 'four'], false, []);
        $expected = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => []];
        $this->assertSame($expected, $result);

        $result = Hash::normalize(['one' => 1, 'two' => 2, 'three' => 3, 'four']);
        $expected = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => null];
        $this->assertSame($expected, $result);

        $result = Hash::normalize(['one' => ['a', 'b', 'c' => 'cee'], 'two' => 2, 'three']);
        $expected = ['one' => ['a', 'b', 'c' => 'cee'], 'two' => 2, 'three' => null];
        $this->assertSame($expected, $result);

        $result = Hash::normalize(['one' => ['a', 'b', 'c' => 'cee'], 'two' => 2, 'three'], true, 'x');
        $expected = ['one' => ['a', 'b', 'c' => 'cee'], 'two' => 2, 'three' => 'x'];
        $this->assertSame($expected, $result);
    }

    /**
     * testContains method
     */
    public function testContains(): void
    {
        $data = ['apple', 'bee', 'cyclops'];
        $this->assertTrue(Hash::contains($data, ['apple']));
        $this->assertFalse(Hash::contains($data, ['data']));

        $a = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about'],
        ];
        $b = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about'],
            2 => ['name' => 'contact'],
            'a' => 'b',
        ];

        $this->assertTrue(Hash::contains($a, $a));
        $this->assertFalse(Hash::contains($a, $b));
        $this->assertTrue(Hash::contains($b, $a));

        $a = [
            ['User' => ['id' => 1]],
            ['User' => ['id' => 2]],
        ];
        $b = [
            ['User' => ['id' => 1]],
            ['User' => ['id' => 2]],
            ['User' => ['id' => 3]],
        ];
        $this->assertTrue(Hash::contains($b, $a));
        $this->assertFalse(Hash::contains($a, $b));

        $a = [0 => 'test', 'string' => null];
        $this->assertTrue(Hash::contains($a, ['string' => null]));

        $a = [0 => 'test', 'string' => null];
        $this->assertTrue(Hash::contains($a, ['test']));
    }

    /**
     * testFilter method
     */
    public function testFilter(): void
    {
        $result = Hash::filter([
            '0',
            false,
            true,
            0,
            0.0,
            ['one thing', 'I can tell you', 'is you got to be', false],
        ]);
        $expected = [
            0 => '0',
            2 => true,
            3 => 0,
            4 => 0.0,
            5 => ['one thing', 'I can tell you', 'is you got to be'],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::filter([1, [false]]);
        $expected = [1];
        $this->assertSame($expected, $result);

        $result = Hash::filter([1, [false, false]]);
        $expected = [1];
        $this->assertSame($expected, $result);

        $result = Hash::filter([1, ['empty', false]]);
        $expected = [1, ['empty']];
        $this->assertSame($expected, $result);

        $result = Hash::filter([1, ['2', false, [3, null]]]);
        $expected = [1, ['2', 2 => [3]]];
        $this->assertSame($expected, $result);

        $this->assertSame([], Hash::filter([]));
    }

    /**
     * testNumericArrayCheck method
     */
    public function testNumeric(): void
    {
        $data = ['one'];
        $this->assertTrue(Hash::numeric(array_keys($data)));

        $data = [1 => 'one'];
        $this->assertFalse(Hash::numeric($data));

        $data = ['one'];
        $this->assertFalse(Hash::numeric($data));

        $data = ['one' => 'two'];
        $this->assertFalse(Hash::numeric($data));

        $data = ['one' => 1];
        $this->assertTrue(Hash::numeric($data));

        $data = [0];
        $this->assertTrue(Hash::numeric($data));

        $data = ['one', 'two', 'three', 'four', 'five'];
        $this->assertTrue(Hash::numeric(array_keys($data)));

        $data = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five'];
        $this->assertTrue(Hash::numeric(array_keys($data)));

        $data = ['1' => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five'];
        $this->assertTrue(Hash::numeric(array_keys($data)));

        $data = ['one', 2 => 'two', 3 => 'three', 4 => 'four', 'a' => 'five'];
        $this->assertFalse(Hash::numeric(array_keys($data)));

        $data = [2.4, 1, 0, -1, -2];
        $this->assertTrue(Hash::numeric($data));
    }

    /**
     * Test passing invalid argument type
     */
    public function testExtractInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type, must be an array or \ArrayAccess instance.');
        Hash::extract('foo', '');
    }

    /**
     * Test the extraction of a single value filtered by another field.
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractSingleValueWithFilteringByAnotherField($data): void
    {
        $result = Hash::extract($data, '{*}.Article[id=1].title');
        $this->assertSame([0 => 'First Article'], $result);

        $result = Hash::extract($data, '{*}.Article[id=2].title');
        $this->assertSame([0 => 'Second Article'], $result);
    }

    /**
     * Test simple paths.
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractBasic($data): void
    {
        $result = Hash::extract($data, '');
        $this->assertSame($data, $result);

        $result = Hash::extract($data, '0.Article.title');
        $this->assertSame(['First Article'], $result);

        $result = Hash::extract($data, '1.Article.title');
        $this->assertSame(['Second Article'], $result);

        $result = Hash::extract([false], '{n}.Something.another_thing');
        $this->assertSame([], $result);
    }

    /**
     * Test the {n} selector
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractNumericKey($data): void
    {
        $result = Hash::extract($data, '{n}.Article.title');
        $expected = [
            'First Article', 'Second Article',
            'Third Article', 'Fourth Article',
            'Fifth Article',
        ];
        $this->assertSame($expected, $result);

        $result = Hash::extract($data, '0.Comment.{n}.user_id');
        $expected = [
            '2', '4',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test the {n} selector with inconsistent arrays
     */
    public function testExtractNumericMixedKeys(): void
    {
        $data = [
            'User' => [
                0 => [
                    'id' => 4,
                    'name' => 'Neo',
                ],
                1 => [
                    'id' => 5,
                    'name' => 'Morpheus',
                ],
                'stringKey' => [
                    'name' => 'Fail',
                ],
            ],
        ];
        $result = Hash::extract($data, 'User.{n}.name');
        $expected = ['Neo', 'Morpheus'];
        $this->assertSame($expected, $result);

        $data = new ArrayObject([
            'User' => new ArrayObject([
                0 => new Entity([
                    'id' => 4,
                    'name' => 'Neo',
                ]),
                1 => new ArrayObject([
                    'id' => 5,
                    'name' => 'Morpheus',
                ]),
                'stringKey' => new ArrayObject([
                    'name' => 'Fail',
                ]),
            ]),
        ]);
        $result = Hash::extract($data, 'User.{n}.name');
        $this->assertSame($expected, $result);

        $data = [
            0 => new Entity([
                'id' => 4,
                'name' => 'Neo',
            ]),
            'stringKey' => new ArrayObject([
                'name' => 'Fail',
            ]),
        ];
        $result = Hash::extract($data, '{n}.name');
        $expected = ['Neo'];
        $this->assertSame($expected, $result);
    }

    /**
     * Test the {n} selector with non-zero based arrays
     */
    public function testExtractNumericNonZero(): void
    {
        $data = [
            1 => [
                'User' => [
                    'id' => 1,
                    'name' => 'John',
                ],
            ],
            2 => [
                'User' => [
                    'id' => 2,
                    'name' => 'Bob',
                ],
            ],
            3 => [
                'User' => [
                    'id' => 3,
                    'name' => 'Tony',
                ],
            ],
        ];
        $result = Hash::extract($data, '{n}.User.name');
        $expected = ['John', 'Bob', 'Tony'];
        $this->assertSame($expected, $result);

        $data = new ArrayObject([
            1 => new ArrayObject([
                'User' => new ArrayObject([
                    'id' => 1,
                    'name' => 'John',
                ]),
            ]),
            2 => new ArrayObject([
                'User' => new ArrayObject([
                    'id' => 2,
                    'name' => 'Bob',
                ]),
            ]),
            3 => new ArrayObject([
                'User' => new ArrayObject([
                    'id' => 3,
                    'name' => 'Tony',
                ]),
            ]),
        ]);
        $result = Hash::extract($data, '{n}.User.name');
        $expected = ['John', 'Bob', 'Tony'];
        $this->assertSame($expected, $result);
    }

    /**
     * Test the {s} selector.
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractStringKey($data): void
    {
        $result = Hash::extract($data, '{n}.{s}.user');
        $expected = [
            'mariano',
            'mariano',
            'mariano',
            'mariano',
            'mariano',
        ];
        $this->assertSame($expected, $result);

        $result = Hash::extract($data, '{n}.{s}.Nesting.test.1');
        $this->assertSame(['foo'], $result);
    }

    /**
     * Test wildcard matcher
     */
    public function testExtractWildcard(): void
    {
        $data = [
            '02000009C5560001' => ['name' => 'Mr. Alphanumeric'],
            '2300000918020101' => ['name' => 'Mr. Numeric'],
            '390000096AB30001' => ['name' => 'Mrs. Alphanumeric'],
            'stuff' => ['name' => 'Ms. Word'],
            123 => ['name' => 'Mr. Number'],
            true => ['name' => 'Ms. Bool'],
        ];
        $result = Hash::extract($data, '{*}.name');
        $expected = [
            'Mr. Alphanumeric',
            'Mr. Numeric',
            'Mrs. Alphanumeric',
            'Ms. Word',
            'Mr. Number',
            'Ms. Bool',
        ];
        $this->assertSame($expected, $result);

        $data = new ArrayObject([
            '02000009C5560001' => new ArrayObject(['name' => 'Mr. Alphanumeric']),
            '2300000918020101' => new ArrayObject(['name' => 'Mr. Numeric']),
            '390000096AB30001' => new ArrayObject(['name' => 'Mrs. Alphanumeric']),
            'stuff' => new ArrayObject(['name' => 'Ms. Word']),
            123 => new ArrayObject(['name' => 'Mr. Number']),
            true => new ArrayObject(['name' => 'Ms. Bool']),
        ]);
        $result = Hash::extract($data, '{*}.name');
        $expected = [
            'Mr. Alphanumeric',
            'Mr. Numeric',
            'Mrs. Alphanumeric',
            'Ms. Word',
            'Mr. Number',
            'Ms. Bool',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test the attribute presence selector.
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractAttributePresence($data): void
    {
        $result = Hash::extract($data, '{n}.Article[published]');
        $expected = [$data[1]['Article']];
        $this->assertSame($expected, $result);

        $result = Hash::extract($data, '{n}.Article[id][published]');
        $expected = [$data[1]['Article']];
        $this->assertSame($expected, $result);
    }

    /**
     * Test = and != operators.
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractAttributeEquality($data): void
    {
        $result = Hash::extract($data, '{n}.Article[id=3]');
        $expected = [$data[2]['Article']];
        $this->assertSame($expected, $result);

        $result = Hash::extract($data, '{n}.Article[id = 3]');
        $expected = [$data[2]['Article']];
        $this->assertSame($expected, $result, 'Whitespace should not matter.');

        $result = Hash::extract($data, '{n}.Article[id!=3]');
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('2', $result[1]['id']);
        $this->assertSame('4', $result[2]['id']);
        $this->assertSame('5', $result[3]['id']);
    }

    /**
     * Test extracting based on attributes with boolean values.
     */
    public function testExtractAttributeBoolean(): void
    {
        $usersArray = [
            [
                'id' => 2,
                'username' => 'johndoe',
                'active' => true,
            ],
            [
                'id' => 5,
                'username' => 'kevin',
                'active' => true,
            ],
            [
                'id' => 9,
                'username' => 'samantha',
                'active' => false,
            ],
        ];

        $usersObject = new ArrayObject([
            new ArrayObject([
                'id' => 2,
                'username' => 'johndoe',
                'active' => true,
            ]),
            new ArrayObject([
                'id' => 5,
                'username' => 'kevin',
                'active' => true,
            ]),
            new ArrayObject([
                'id' => 9,
                'username' => 'samantha',
                'active' => false,
            ]),
        ]);

        foreach ([$usersArray, $usersObject] as $users) {
            $result = Hash::extract($users, '{n}[active=0]');
            $this->assertCount(1, $result);
            $this->assertSame($users[2], $result[0]);

            $result = Hash::extract($users, '{n}[active=false]');
            $this->assertCount(1, $result);
            $this->assertSame($users[2], $result[0]);

            $result = Hash::extract($users, '{n}[active=1]');
            $this->assertCount(2, $result);
            $this->assertSame($users[0], $result[0]);
            $this->assertSame($users[1], $result[1]);

            $result = Hash::extract($users, '{n}[active=true]');
            $this->assertCount(2, $result);
            $this->assertSame($users[0], $result[0]);
            $this->assertSame($users[1], $result[1]);
        }
    }

    /**
     * Test that attribute matchers don't cause errors on scalar data.
     */
    public function testExtractAttributeEqualityOnScalarValue(): void
    {
        $dataArray = [
            'Entity' => [
                'id' => 1,
                'data1' => 'value',
            ],
        ];
        $dataObject = new ArrayObject([
            'Entity' => new ArrayObject([
                'id' => 1,
                'data1' => 'value',
            ]),
        ]);

        foreach ([$dataArray, $dataObject] as $data) {
            $result = Hash::extract($data, 'Entity[id=1].data1');
            $this->assertSame(['value'], $result);

            $data = ['Entity' => false];
            $result = Hash::extract($data, 'Entity[id=1].data1');
            $this->assertSame([], $result);
        }
    }

    /**
     * Test comparison operators.
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractAttributeComparison($data): void
    {
        $result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2]');
        $expected = [$data[0]['Comment'][1]];
        $this->assertSame($expected, $result);
        $this->assertSame('4', $expected[0]['user_id']);

        $result = Hash::extract($data, '{n}.Comment.{n}[user_id >= 4]');
        $expected = [$data[0]['Comment'][1]];
        $this->assertSame($expected, $result);
        $this->assertSame('4', $expected[0]['user_id']);

        $result = Hash::extract($data, '{n}.Comment.{n}[user_id < 3]');
        $expected = [$data[0]['Comment'][0]];
        $this->assertSame($expected, $result);
        $this->assertSame('2', $expected[0]['user_id']);

        $result = Hash::extract($data, '{n}.Comment.{n}[user_id <= 2]');
        $expected = [$data[0]['Comment'][0]];
        $this->assertSame($expected, $result);
        $this->assertSame('2', $expected[0]['user_id']);
    }

    /**
     * Test multiple attributes with conditions.
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractAttributeMultiple($data): void
    {
        $result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2][id=1]');
        $this->assertEmpty($result);

        $result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2][id=2]');
        $expected = [$data[0]['Comment'][1]];
        $this->assertSame($expected, $result);
        $this->assertSame('4', $expected[0]['user_id']);
    }

    /**
     * Test attribute pattern matching.
     *
     * @dataProvider articleDataSets
     * @param \ArrayAccess|array $data
     */
    public function testExtractAttributePattern($data): void
    {
        $result = Hash::extract($data, '{n}.Article[title=/^First/]');
        $expected = [$data[0]['Article']];
        $this->assertSame($expected, $result);

        $result = Hash::extract($data, '{n}.Article[title=/^Fir[a-z]+/]');
        $expected = [$data[0]['Article']];
        $this->assertSame($expected, $result);
    }

    /**
     * Test that extract() + matching can hit null things.
     */
    public function testExtractMatchesNull(): void
    {
        $data = [
            'Country' => [
                ['name' => 'Canada'],
                ['name' => 'Australia'],
                ['name' => null],
            ],
        ];
        $result = Hash::extract($data, 'Country.{n}[name=/Canada|^$/]');
        $expected = [
            [
                'name' => 'Canada',
            ],
            [
                'name' => null,
            ],
        ];
        $this->assertSame($expected, $result);

        $data = new ArrayObject([
            'Country' => new ArrayObject([
                ['name' => 'Canada'],
                ['name' => 'Australia'],
                ['name' => null],
            ]),
        ]);
        $result = Hash::extract($data, 'Country.{n}[name=/Canada|^$/]');
        $this->assertSame($expected, $result);
    }

    /**
     * Test extracting attributes with string
     */
    public function testExtractAttributeString(): void
    {
        $data = [
            ['value' => 0],
            ['value' => 3],
            ['value' => 'string-value'],
            ['value' => new FrozenTime('2010-01-05 01:23:45')],
        ];

        // check _matches does not work as `0 == 'string-value'`
        $expected = [$data[2]];
        $result = Hash::extract($data, '{n}[value=string-value]');
        $this->assertSame($expected, $result);

        // check _matches work with object implements __toString()
        $expected = [$data[3]];
        $result = Hash::extract($data, sprintf('{n}[value=%s]', $data[3]['value']));
        $this->assertSame($expected, $result);

        // check _matches does not work as `3 == '3 people'`
        $unexpected = $data[1];
        $result = Hash::extract($data, '{n}[value=3people]');
        $this->assertNotContains($unexpected, $result);
    }

    /**
     * Test that uneven keys are handled correctly.
     */
    public function testExtractUnevenKeys(): void
    {
        $data = [
            'Level1' => [
                'Level2' => ['test1', 'test2'],
                'Level2bis' => ['test3', 'test4'],
            ],
        ];
        $this->assertSame(
            ['test1', 'test2'],
            Hash::extract($data, 'Level1.Level2')
        );
        $this->assertSame(
            ['test3', 'test4'],
            Hash::extract($data, 'Level1.Level2bis')
        );

        $data = new ArrayObject([
            'Level1' => new ArrayObject([
                'Level2' => ['test1', 'test2'],
                'Level2bis' => ['test3', 'test4'],
            ]),
        ]);
        $this->assertSame(
            ['test1', 'test2'],
            Hash::extract($data, 'Level1.Level2')
        );
        $this->assertSame(
            ['test3', 'test4'],
            Hash::extract($data, 'Level1.Level2bis')
        );

        $data = [
            'Level1' => [
                'Level2bis' => [
                    ['test3', 'test4'],
                    ['test5', 'test6'],
                ],
            ],
        ];
        $expected = [
            ['test3', 'test4'],
            ['test5', 'test6'],
        ];
        $this->assertSame($expected, Hash::extract($data, 'Level1.Level2bis'));

        $data['Level1']['Level2'] = ['test1', 'test2'];
        $this->assertSame($expected, Hash::extract($data, 'Level1.Level2bis'));

        $data = new ArrayObject([
            'Level1' => new ArrayObject([
                'Level2bis' => [
                    ['test3', 'test4'],
                    ['test5', 'test6'],
                ],
            ]),
        ]);
        $this->assertSame($expected, Hash::extract($data, 'Level1.Level2bis'));

        $data['Level1']['Level2'] = ['test1', 'test2'];
        $this->assertSame($expected, Hash::extract($data, 'Level1.Level2bis'));
    }

    /**
     * Tests that objects as values handled correctly.
     */
    public function testExtractObjects(): void
    {
        $data = [
            'root' => [
                'array' => new ArrayObject([
                    'foo' => 'bar',
                ]),
                'created' => new FrozenTime('2010-01-05'),
            ],
        ];

        $result = Hash::extract($data, 'root.created');
        $this->assertSame([$data['root']['created']], $result);

        $result = Hash::extract($data, 'root.array');
        $this->assertSame(['foo' => 'bar'], $result);

        $result = Hash::extract($data, 'root.array.foo');
        $this->assertSame(['bar'], $result);
    }

    /**
     * testSort method
     */
    public function testSort(): void
    {
        $result = Hash::sort([], '{n}.name');
        $this->assertSame([], $result);

        $a = [
            0 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']],
            ],
            1 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']],
            ],
        ];
        $b = [
            0 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']],
            ],
            1 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']],
            ],
        ];
        $a = Hash::sort($a, '{n}.Friend.{n}.name');
        $this->assertSame($a, $b);

        $b = [
            0 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']],
            ],
            1 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']],
            ],
        ];
        $a = [
            0 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']],
            ],
            1 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']],
            ],
        ];
        $a = Hash::sort($a, '{n}.Friend.{n}.name', 'desc');
        $this->assertSame($a, $b);

        $a = [
            0 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']],
            ],
            1 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']],
            ],
            2 => [
                'Person' => ['name' => 'Adam'],
                'Friend' => [['name' => 'Bob']],
            ],
        ];
        $b = [
            0 => [
                'Person' => ['name' => 'Adam'],
                'Friend' => [['name' => 'Bob']],
            ],
            1 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']],
            ],
            2 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']],
            ],
        ];
        $a = Hash::sort($a, '{n}.Person.name', 'asc');
        $this->assertSame($a, $b);

        $a = [
            0 => ['Person' => ['name' => 'Jeff']],
            1 => ['Shirt' => ['color' => 'black']],
        ];
        $b = [
            0 => ['Shirt' => ['color' => 'black']],
            1 => ['Person' => ['name' => 'Jeff']],
        ];
        $a = Hash::sort($a, '{n}.Person.name', 'ASC', 'STRING');
        $this->assertSame($a, $b);

        $names = [
            ['employees' => [
                ['name' => ['first' => 'John', 'last' => 'Doe']]],
            ],
            ['employees' => [
                ['name' => ['first' => 'Jane', 'last' => 'Doe']]],
            ],
            ['employees' => [['name' => []]]],
            ['employees' => [['name' => []]]],
        ];
        $result = Hash::sort($names, '{n}.employees.0.name', 'asc');
        $expected = [
            ['employees' => [
                ['name' => ['first' => 'John', 'last' => 'Doe']]],
            ],
            ['employees' => [
                ['name' => ['first' => 'Jane', 'last' => 'Doe']]],
            ],
            ['employees' => [['name' => []]]],
            ['employees' => [['name' => []]]],
        ];
        $this->assertSame($expected, $result);

        $a = [
            'SU' => [
                'total_fulfillable' => 2,
            ],
            'AA' => [
                'total_fulfillable' => 1,
            ],
            'LX' => [
                'total_fulfillable' => 0,
            ],
            'BL' => [
                'total_fulfillable' => 3,
            ],
        ];
        $expected = [
            'LX' => [
                'total_fulfillable' => 0,
            ],
            'AA' => [
                'total_fulfillable' => 1,
            ],
            'SU' => [
                'total_fulfillable' => 2,
            ],
            'BL' => [
                'total_fulfillable' => 3,
            ],
        ];
        $result = Hash::sort($a, '{s}.total_fulfillable', 'asc');
        $this->assertSame($expected, $result);
    }

    /**
     * Test sort() with numeric option.
     */
    public function testSortNumeric(): void
    {
        $items = [
            ['Item' => ['price' => '155,000']],
            ['Item' => ['price' => '139,000']],
            ['Item' => ['price' => '275,622']],
            ['Item' => ['price' => '230,888']],
            ['Item' => ['price' => '66,000']],
        ];
        $result = Hash::sort($items, '{n}.Item.price', 'asc', 'numeric');
        $expected = [
            ['Item' => ['price' => '66,000']],
            ['Item' => ['price' => '139,000']],
            ['Item' => ['price' => '155,000']],
            ['Item' => ['price' => '230,888']],
            ['Item' => ['price' => '275,622']],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::sort($items, '{n}.Item.price', 'desc', 'numeric');
        $expected = [
            ['Item' => ['price' => '275,622']],
            ['Item' => ['price' => '230,888']],
            ['Item' => ['price' => '155,000']],
            ['Item' => ['price' => '139,000']],
            ['Item' => ['price' => '66,000']],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test natural sorting.
     */
    public function testSortNatural(): void
    {
        $items = [
            ['Item' => ['image' => 'img1.jpg']],
            ['Item' => ['image' => 'img99.jpg']],
            ['Item' => ['image' => 'img12.jpg']],
            ['Item' => ['image' => 'img10.jpg']],
            ['Item' => ['image' => 'img2.jpg']],
        ];
        $result = Hash::sort($items, '{n}.Item.image', 'desc', 'natural');
        $expected = [
            ['Item' => ['image' => 'img99.jpg']],
            ['Item' => ['image' => 'img12.jpg']],
            ['Item' => ['image' => 'img10.jpg']],
            ['Item' => ['image' => 'img2.jpg']],
            ['Item' => ['image' => 'img1.jpg']],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::sort($items, '{n}.Item.image', 'asc', 'natural');
        $expected = [
            ['Item' => ['image' => 'img1.jpg']],
            ['Item' => ['image' => 'img2.jpg']],
            ['Item' => ['image' => 'img10.jpg']],
            ['Item' => ['image' => 'img12.jpg']],
            ['Item' => ['image' => 'img99.jpg']],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test sort() with locale option.
     */
    public function testSortLocale(): void
    {
        // get the current locale
        $original = setlocale(LC_COLLATE, '0');
        $updated = setlocale(LC_COLLATE, 'de_DE.utf8');
        $this->skipIf($updated === false, 'Could not set locale to de_DE.utf8, skipping test.');

        $items = [
            ['Item' => ['entry' => 'Übergabe']],
            ['Item' => ['entry' => 'Ostfriesland']],
            ['Item' => ['entry' => 'Äpfel']],
            ['Item' => ['entry' => 'Apfel']],
        ];

        $result = Hash::sort($items, '{n}.Item.entry', 'asc', 'locale');
        $expected = [
            ['Item' => ['entry' => 'Apfel']],
            ['Item' => ['entry' => 'Äpfel']],
            ['Item' => ['entry' => 'Ostfriesland']],
            ['Item' => ['entry' => 'Übergabe']],
        ];

        setlocale(LC_COLLATE, $original);
        $this->assertSame($expected, $result);
    }

    /**
     * Test that sort() with 'natural' type will fallback to 'regular' as SORT_NATURAL is introduced in PHP 5.4
     */
    public function testSortNaturalFallbackToRegular(): void
    {
        $a = [
            0 => ['Person' => ['name' => 'Jeff']],
            1 => ['Shirt' => ['color' => 'black']],
        ];
        $b = [
            0 => ['Shirt' => ['color' => 'black']],
            1 => ['Person' => ['name' => 'Jeff']],
        ];
        $sorted = Hash::sort($a, '{n}.Person.name', 'asc', 'natural');
        $this->assertSame($sorted, $b);
    }

    /**
     * test sorting with out of order keys.
     */
    public function testSortWithOutOfOrderKeys(): void
    {
        $data = [
            9 => ['class' => 510, 'test2' => 2],
            1 => ['class' => 500, 'test2' => 1],
            2 => ['class' => 600, 'test2' => 2],
            5 => ['class' => 625, 'test2' => 4],
            0 => ['class' => 605, 'test2' => 3],
        ];
        $expected = [
            ['class' => 500, 'test2' => 1],
            ['class' => 510, 'test2' => 2],
            ['class' => 600, 'test2' => 2],
            ['class' => 605, 'test2' => 3],
            ['class' => 625, 'test2' => 4],
        ];
        $result = Hash::sort($data, '{n}.class', 'asc');
        $this->assertSame($expected, $result);

        $result = Hash::sort($data, '{n}.test2', 'asc');
        $this->assertSame($expected, $result);
    }

    /**
     * test sorting with string keys.
     */
    public function testSortString(): void
    {
        $toSort = [
            'four' => ['number' => 4, 'some' => 'foursome'],
            'six' => ['number' => 6, 'some' => 'sixsome'],
            'five' => ['number' => 5, 'some' => 'fivesome'],
            'two' => ['number' => 2, 'some' => 'twosome'],
            'three' => ['number' => 3, 'some' => 'threesome'],
        ];
        $sorted = Hash::sort($toSort, '{s}.number', 'asc');
        $expected = [
            'two' => ['number' => 2, 'some' => 'twosome'],
            'three' => ['number' => 3, 'some' => 'threesome'],
            'four' => ['number' => 4, 'some' => 'foursome'],
            'five' => ['number' => 5, 'some' => 'fivesome'],
            'six' => ['number' => 6, 'some' => 'sixsome'],
        ];
        $this->assertSame($expected, $sorted);

        $menus = [
            'blogs' => ['title' => 'Blogs', 'weight' => 3],
            'comments' => ['title' => 'Comments', 'weight' => 2],
            'users' => ['title' => 'Users', 'weight' => 1],
        ];
        $expected = [
            'users' => ['title' => 'Users', 'weight' => 1],
            'comments' => ['title' => 'Comments', 'weight' => 2],
            'blogs' => ['title' => 'Blogs', 'weight' => 3],
        ];
        $result = Hash::sort($menus, '{s}.weight', 'ASC');
        $this->assertSame($expected, $result);
    }

    /**
     * test sorting with string ignoring case.
     */
    public function testSortStringIgnoreCase(): void
    {
        $toSort = [
            ['Item' => ['name' => 'bar']],
            ['Item' => ['name' => 'Baby']],
            ['Item' => ['name' => 'Baz']],
            ['Item' => ['name' => 'bat']],
        ];
        $sorted = Hash::sort($toSort, '{n}.Item.name', 'asc', ['type' => 'string', 'ignoreCase' => true]);
        $expected = [
            ['Item' => ['name' => 'Baby']],
            ['Item' => ['name' => 'bar']],
            ['Item' => ['name' => 'bat']],
            ['Item' => ['name' => 'Baz']],
        ];
        $this->assertSame($expected, $sorted);
    }

    /**
     * test regular sorting ignoring case.
     */
    public function testSortRegularIgnoreCase(): void
    {
        $toSort = [
            ['Item' => ['name' => 'bar']],
            ['Item' => ['name' => 'Baby']],
            ['Item' => ['name' => 'Baz']],
            ['Item' => ['name' => 'bat']],
        ];
        $sorted = Hash::sort($toSort, '{n}.Item.name', 'asc', ['type' => 'regular', 'ignoreCase' => true]);
        $expected = [
            ['Item' => ['name' => 'Baby']],
            ['Item' => ['name' => 'bar']],
            ['Item' => ['name' => 'bat']],
            ['Item' => ['name' => 'Baz']],
        ];
        $this->assertSame($expected, $sorted);
    }

    /**
     * Test sorting on a nested key that is sometimes undefined.
     */
    public function testSortSparse(): void
    {
        $data = [
            [
                'id' => 1,
                'title' => 'element 1',
                'extra' => 1,
            ],
            [
                'id' => 2,
                'title' => 'element 2',
                'extra' => 2,
            ],
            [
                'id' => 3,
                'title' => 'element 3',
            ],
            [
                'id' => 4,
                'title' => 'element 4',
                'extra' => 4,
            ],
        ];
        $result = Hash::sort($data, '{n}.extra', 'desc', 'natural');
        $expected = [
            [
                'id' => 4,
                'title' => 'element 4',
                'extra' => 4,
            ],
            [
                'id' => 2,
                'title' => 'element 2',
                'extra' => 2,
            ],
            [
                'id' => 1,
                'title' => 'element 1',
                'extra' => 1,
            ],
            [
                'id' => 3,
                'title' => 'element 3',
            ],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test insert()
     */
    public function testInsertSimple(): void
    {
        $a = [
            'pages' => ['name' => 'page'],
        ];
        $result = Hash::insert($a, 'files', ['name' => 'files']);
        $expected = [
            'pages' => ['name' => 'page'],
            'files' => ['name' => 'files'],
        ];
        $this->assertSame($expected, $result);

        $a = [
            'pages' => ['name' => 'page'],
        ];
        $result = Hash::insert($a, 'pages.name', []);
        $expected = [
            'pages' => ['name' => []],
        ];
        $this->assertSame($expected, $result);

        $a = [
            'foo' => ['bar' => 'baz'],
        ];
        $result = Hash::insert($a, 'some.0123.path', ['foo' => ['bar' => 'baz']]);
        $expected = ['foo' => ['bar' => 'baz']];
        $this->assertSame($expected, Hash::get($result, 'some.0123.path'));
    }

    /**
     * Test inserting with multiple values.
     */
    public function testInsertMulti(): void
    {
        $data = static::articleData();

        $result = Hash::insert($data, '{n}.Article.insert', 'value');
        $this->assertSame('value', $result[0]['Article']['insert']);
        $this->assertSame('value', $result[1]['Article']['insert']);

        $result = Hash::insert($data, '{n}.Comment.{n}.insert', 'value');
        $this->assertSame('value', $result[0]['Comment'][0]['insert']);
        $this->assertSame('value', $result[0]['Comment'][1]['insert']);

        $data = [
            0 => ['Item' => ['id' => 1, 'title' => 'first']],
            1 => ['Item' => ['id' => 2, 'title' => 'second']],
            2 => ['Item' => ['id' => 3, 'title' => 'third']],
            3 => ['Item' => ['id' => 4, 'title' => 'fourth']],
            4 => ['Item' => ['id' => 5, 'title' => 'fifth']],
        ];
        $result = Hash::insert($data, '{n}.Item[id=/\b2|\b4/]', ['test' => 2]);
        $expected = [
            0 => ['Item' => ['id' => 1, 'title' => 'first']],
            1 => ['Item' => ['id' => 2, 'title' => 'second', 'test' => 2]],
            2 => ['Item' => ['id' => 3, 'title' => 'third']],
            3 => ['Item' => ['id' => 4, 'title' => 'fourth', 'test' => 2]],
            4 => ['Item' => ['id' => 5, 'title' => 'fifth']],
        ];
        $this->assertSame($expected, $result);

        $data[3]['testable'] = true;
        $result = Hash::insert($data, '{n}[testable].Item[id=/\b2|\b4/].test', 2);
        $expected = [
            0 => ['Item' => ['id' => 1, 'title' => 'first']],
            1 => ['Item' => ['id' => 2, 'title' => 'second']],
            2 => ['Item' => ['id' => 3, 'title' => 'third']],
            3 => ['Item' => ['id' => 4, 'title' => 'fourth', 'test' => 2], 'testable' => true],
            4 => ['Item' => ['id' => 5, 'title' => 'fifth']],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * test insert() with {s} placeholders and conditions.
     */
    public function testInsertMultiWord(): void
    {
        $data = static::articleData();

        $result = Hash::insert($data, '{n}.{s}.insert', 'value');
        $this->assertSame('value', $result[0]['Article']['insert']);
        $this->assertSame('value', $result[1]['Article']['insert']);

        $data = [
            0 => ['obj' => new stdClass(), 'Item' => ['id' => 1, 'title' => 'first']],
            1 => ['float' => 1.5, 'Item' => ['id' => 2, 'title' => 'second']],
            2 => ['int' => 1, 'Item' => ['id' => 3, 'title' => 'third']],
            3 => ['str' => 'yes', 'Item' => ['id' => 3, 'title' => 'third']],
            4 => ['bool' => true, 'Item' => ['id' => 4, 'title' => 'fourth']],
            5 => ['null' => null, 'Item' => ['id' => 5, 'title' => 'fifth']],
            6 => ['arrayish' => new ArrayObject(['val']), 'Item' => ['id' => 6, 'title' => 'sixth']],
        ];
        $result = Hash::insert($data, '{n}.{s}[id=4].new', 'value');
        $this->assertEquals('value', $result[4]['Item']['new']);
    }

    /**
     * Test that insert() can insert data over a string value.
     */
    public function testInsertOverwriteStringValue(): void
    {
        $data = [
            'Some' => [
                'string' => 'value',
            ],
        ];
        $result = Hash::insert($data, 'Some.string.value', ['values']);
        $expected = [
            'Some' => [
                'string' => [
                    'value' => ['values'],
                ],
            ],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test remove() method.
     */
    public function testRemove(): void
    {
        $a = [
            'pages' => ['name' => 'page'],
            'files' => ['name' => 'files'],
        ];

        $result = Hash::remove($a, 'files');
        $expected = [
            'pages' => ['name' => 'page'],
        ];
        $this->assertSame($expected, $result);

        $a = [
            'pages' => [
                0 => ['name' => 'main'],
                1 => [
                    'name' => 'about',
                    'vars' => ['title' => 'page title'],
                ],
            ],
        ];

        $result = Hash::remove($a, 'pages.1.vars');
        $expected = [
            'pages' => [
                0 => ['name' => 'main'],
                1 => ['name' => 'about'],
            ],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::remove($a, 'pages.2.vars');
        $expected = $a;
        $this->assertSame($expected, $result);

        $a = [
            0 => [
                'name' => 'pages',
            ],
            1 => [
                'name' => 'files',
            ],
        ];

        $result = Hash::remove($a, '{n}[name=files]');
        $expected = [
            0 => [
                'name' => 'pages',
            ],
        ];
        $this->assertSame($expected, $result);

        $array = [
            0 => 'foo',
            1 => [
                0 => 'baz',
            ],
        ];
        $expected = $array;
        $result = Hash::remove($array, '{n}.part');
        $this->assertSame($expected, $result);
        $result = Hash::remove($array, '{n}.{n}.part');
        $this->assertSame($expected, $result);

        $array = [
            'foo' => 'string',
        ];
        $expected = $array;
        $result = Hash::remove($array, 'foo.bar');
        $this->assertSame($expected, $result);

        $array = [
            'foo' => 'string',
            'bar' => [
                0 => 'a',
                1 => 'b',
            ],
        ];
        $expected = [
            'foo' => 'string',
            'bar' => [
                1 => 'b',
            ],
        ];
        $result = Hash::remove($array, '{s}.0');
        $this->assertSame($expected, $result);

        $array = [
            'foo' => [
                0 => 'a',
                1 => 'b',
            ],
        ];
        $expected = [
            'foo' => [
                1 => 'b',
            ],
        ];
        $result = Hash::remove($array, 'foo[1=b].0');
        $this->assertSame($expected, $result);
    }

    /**
     * Test removing multiple values.
     */
    public function testRemoveMulti(): void
    {
        $data = static::articleData();

        $result = Hash::remove($data, '{n}.Article.title');
        $this->assertFalse(isset($result[0]['Article']['title']));
        $this->assertFalse(isset($result[1]['Article']['title']));

        $result = Hash::remove($data, '{n}.Article.{s}');
        $this->assertFalse(isset($result[0]['Article']['id']));
        $this->assertFalse(isset($result[0]['Article']['user_id']));
        $this->assertFalse(isset($result[0]['Article']['title']));
        $this->assertFalse(isset($result[0]['Article']['body']));

        $data = [
            0 => ['Item' => ['id' => 1, 'title' => 'first']],
            1 => ['Item' => ['id' => 2, 'title' => 'second']],
            2 => ['Item' => ['id' => 3, 'title' => 'third']],
            3 => ['Item' => ['id' => 4, 'title' => 'fourth']],
            4 => ['Item' => ['id' => 5, 'title' => 'fifth']],
        ];

        $result = Hash::remove($data, '{n}.Item[id=/\b2|\b4/]');
        $expected = [
            0 => ['Item' => ['id' => 1, 'title' => 'first']],
            2 => ['Item' => ['id' => 3, 'title' => 'third']],
            4 => ['Item' => ['id' => 5, 'title' => 'fifth']],
        ];
        $this->assertSame($expected, $result);

        $data[3]['testable'] = true;
        $result = Hash::remove($data, '{n}[testable].Item[id=/\b2|\b4/].title');
        $expected = [
            0 => ['Item' => ['id' => 1, 'title' => 'first']],
            1 => ['Item' => ['id' => 2, 'title' => 'second']],
            2 => ['Item' => ['id' => 3, 'title' => 'third']],
            3 => ['Item' => ['id' => 4], 'testable' => true],
            4 => ['Item' => ['id' => 5, 'title' => 'fifth']],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * testCheck method
     */
    public function testCheck(): void
    {
        $set = [
            'My Index 1' => ['First' => 'The first item'],
        ];
        $this->assertTrue(Hash::check($set, 'My Index 1.First'));
        $this->assertTrue(Hash::check($set, 'My Index 1'));

        $set = [
            'My Index 1' => [
                'First' => [
                    'Second' => [
                        'Third' => [
                            'Fourth' => 'Heavy. Nesting.',
                        ],
                    ],
                ],
            ],
        ];
        $this->assertTrue(Hash::check($set, 'My Index 1.First.Second'));
        $this->assertTrue(Hash::check($set, 'My Index 1.First.Second.Third'));
        $this->assertTrue(Hash::check($set, 'My Index 1.First.Second.Third.Fourth'));
        $this->assertFalse(Hash::check($set, 'My Index 1.First.Seconds.Third.Fourth'));
    }

    /**
     * testCombine method
     */
    public function testCombine(): void
    {
        $result = Hash::combine([], '{n}.User.id', '{n}.User.Data');
        $this->assertEmpty($result);

        $a = static::userData();

        $result = Hash::combine($a, '{n}.User.id');
        $expected = [2 => null, 14 => null, 25 => null];
        $this->assertSame($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.nonexistent');
        $expected = [2 => null, 14 => null, 25 => null];
        $this->assertSame($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data');
        $expected = [
            2 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
            14 => ['user' => 'phpnut', 'name' => 'Larry E. Masters'],
            25 => ['user' => 'gwoo', 'name' => 'The Gwoo']];
        $this->assertSame($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name');
        $expected = [
            2 => 'Mariano Iglesias',
            14 => 'Larry E. Masters',
            25 => 'The Gwoo'];
        $this->assertSame($expected, $result);
    }

    /**
     * test combine() with null key path.
     */
    public function testCombineWithNullKeyPath(): void
    {
        $result = Hash::combine([], null, '{n}.User.Data');
        $this->assertEmpty($result);

        $a = static::userData();

        $result = Hash::combine($a, null);
        $expected = [0 => null, 1 => null, 2 => null];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, null, '{n}.User.nonexistent');
        $expected = [0 => null, 1 => null, 2 => null];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, null, '{n}.User.Data');
        $expected = [
            0 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
            1 => ['user' => 'phpnut', 'name' => 'Larry E. Masters'],
            2 => ['user' => 'gwoo', 'name' => 'The Gwoo']];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, null, '{n}.User.Data.name');
        $expected = [
            0 => 'Mariano Iglesias',
            1 => 'Larry E. Masters',
            2 => 'The Gwoo'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test combine() giving errors on key/value length mismatches.
     */
    public function testCombineErrorMissingValue(): void
    {
        $this->expectException(RuntimeException::class);
        $data = [
            ['User' => ['id' => 1, 'name' => 'mark']],
            ['User' => ['name' => 'jose']],
        ];
        Hash::combine($data, '{n}.User.id', '{n}.User.name');
    }

    /**
     * test combine() giving errors on key/value length mismatches.
     */
    public function testCombineErrorMissingKey(): void
    {
        $this->expectException(RuntimeException::class);
        $data = [
            ['User' => ['id' => 1, 'name' => 'mark']],
            ['User' => ['id' => 2]],
        ];
        Hash::combine($data, '{n}.User.id', '{n}.User.name');
    }

    /**
     * test combine() with a group path.
     */
    public function testCombineWithGroupPath(): void
    {
        $a = static::userData();

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
        $expected = [
            1 => [
                2 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
                25 => ['user' => 'gwoo', 'name' => 'The Gwoo'],
            ],
            2 => [
                14 => ['user' => 'phpnut', 'name' => 'Larry E. Masters'],
            ],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::combine($a, null, '{n}.User.Data', '{n}.User.group_id');
        $expected = [
            1 => [
                0 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
                1 => ['user' => 'gwoo', 'name' => 'The Gwoo'],
            ],
            2 => [
                0 => ['user' => 'phpnut', 'name' => 'Larry E. Masters'],
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
        $expected = [
            1 => [
                2 => 'Mariano Iglesias',
                25 => 'The Gwoo',
            ],
            2 => [
                14 => 'Larry E. Masters',
            ],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::combine($a, null, '{n}.User.Data.name', '{n}.User.group_id');
        $expected = [
            1 => [
                0 => 'Mariano Iglesias',
                1 => 'The Gwoo',
            ],
            2 => [
                0 => 'Larry E. Masters',
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
        $expected = [
            1 => [
                2 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
                25 => ['user' => 'gwoo', 'name' => 'The Gwoo'],
            ],
            2 => [
                14 => ['user' => 'phpnut', 'name' => 'Larry E. Masters'],
            ],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::combine($a, null, '{n}.User.Data', '{n}.User.group_id');
        $expected = [
            1 => [
                0 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
                1 => ['user' => 'gwoo', 'name' => 'The Gwoo'],
            ],
            2 => [
                0 => ['user' => 'phpnut', 'name' => 'Larry E. Masters'],
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
        $expected = [
            1 => [
                2 => 'Mariano Iglesias',
                25 => 'The Gwoo',
            ],
            2 => [
                14 => 'Larry E. Masters',
            ],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::combine($a, null, '{n}.User.Data.name', '{n}.User.group_id');
        $expected = [
            1 => [
                0 => 'Mariano Iglesias',
                1 => 'The Gwoo',
            ],
            2 => [
                0 => 'Larry E. Masters',
            ],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test combine with formatting rules.
     */
    public function testCombineWithFormatting(): void
    {
        $a = static::userData();

        $result = Hash::combine(
            $a,
            '{n}.User.id',
            ['%1$s: %2$s', '{n}.User.Data.user', '{n}.User.Data.name'],
            '{n}.User.group_id'
        );
        $expected = [
            1 => [
                2 => 'mariano.iglesias: Mariano Iglesias',
                25 => 'gwoo: The Gwoo',
            ],
            2 => [
                14 => 'phpnut: Larry E. Masters',
            ],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::combine(
            $a,
            null,
            ['%1$s: %2$s', '{n}.User.Data.user', '{n}.User.Data.name'],
            '{n}.User.group_id'
        );
        $expected = [
            1 => [
                0 => 'mariano.iglesias: Mariano Iglesias',
                1 => 'gwoo: The Gwoo',
            ],
            2 => [
                0 => 'phpnut: Larry E. Masters',
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine(
            $a,
            [
                '%s: %s',
                '{n}.User.Data.user',
                '{n}.User.Data.name',
            ],
            '{n}.User.id'
        );
        $expected = [
            'mariano.iglesias: Mariano Iglesias' => 2,
            'phpnut: Larry E. Masters' => 14,
            'gwoo: The Gwoo' => 25,
        ];
        $this->assertSame($expected, $result);

        $result = Hash::combine(
            $a,
            ['%1$s: %2$d', '{n}.User.Data.user', '{n}.User.id'],
            '{n}.User.Data.name'
        );
        $expected = [
            'mariano.iglesias: 2' => 'Mariano Iglesias',
            'phpnut: 14' => 'Larry E. Masters',
            'gwoo: 25' => 'The Gwoo',
        ];
        $this->assertSame($expected, $result);

        $result = Hash::combine(
            $a,
            ['%2$d: %1$s', '{n}.User.Data.user', '{n}.User.id'],
            '{n}.User.Data.name'
        );
        $expected = [
            '2: mariano.iglesias' => 'Mariano Iglesias',
            '14: phpnut' => 'Larry E. Masters',
            '25: gwoo' => 'The Gwoo',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * testFormat method
     */
    public function testFormat(): void
    {
        $data = static::userData();

        $result = Hash::format(
            $data,
            ['{n}.User.Data.user', '{n}.User.id'],
            '%s, %s'
        );
        $expected = [
            'mariano.iglesias, 2',
            'phpnut, 14',
            'gwoo, 25',
        ];
        $this->assertSame($expected, $result);

        $result = Hash::format(
            $data,
            ['{n}.User.Data.user', '{n}.User.id'],
            '%2$s, %1$s'
        );
        $expected = [
            '2, mariano.iglesias',
            '14, phpnut',
            '25, gwoo',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * testFormattingNullValues method
     */
    public function testFormatNullValues(): void
    {
        $data = [
            ['Person' => [
                'first_name' => 'Nate', 'last_name' => 'Abele', 'city' => 'Boston', 'state' => 'MA', 'something' => '42',
            ]],
            ['Person' => [
                'first_name' => 'Larry', 'last_name' => 'Masters', 'city' => 'Boondock', 'state' => 'TN', 'something' => null,
            ]],
            ['Person' => [
                'first_name' => 'Garrett', 'last_name' => 'Woodworth', 'city' => 'Venice Beach', 'state' => 'CA', 'something' => null,
            ]],
        ];

        $result = Hash::format($data, ['{n}.Person.something'], '%s');
        $expected = ['42', '', ''];
        $this->assertSame($expected, $result);

        $result = Hash::format($data, ['{n}.Person.city', '{n}.Person.something'], '%s, %s');
        $expected = ['Boston, 42', 'Boondock, ', 'Venice Beach, '];
        $this->assertSame($expected, $result);
    }

    /**
     * Test map()
     */
    public function testMap(): void
    {
        $data = static::articleData();

        $result = Hash::map($data, '{n}.Article.id', [$this, 'mapCallback']);
        $expected = [2, 4, 6, 8, 10];
        $this->assertSame($expected, $result);
    }

    /**
     * testApply
     */
    public function testApply(): void
    {
        $data = static::articleData();

        $result = Hash::apply($data, '{n}.Article.id', 'array_sum');
        $this->assertSame(15, $result);
    }

    /**
     * Test reduce()
     */
    public function testReduce(): void
    {
        $data = static::articleData();

        $result = Hash::reduce($data, '{n}.Article.id', [$this, 'reduceCallback']);
        $this->assertSame(15, $result);
    }

    /**
     * testing method for map callbacks.
     *
     * @param mixed $value Value
     * @return mixed
     */
    public function mapCallback($value)
    {
        return $value * 2;
    }

    /**
     * testing method for reduce callbacks.
     *
     * @param mixed $one First param
     * @param mixed $two Second param
     * @return mixed
     */
    public function reduceCallback($one, $two)
    {
        return $one + $two;
    }

    /**
     * test Hash nest with a normal model result set. For kicks rely on Hash nest detecting the key names
     * automatically
     */
    public function testNestModel(): void
    {
        $input = [
            [
                'ModelName' => [
                    'id' => 1,
                    'parent_id' => null,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 2,
                    'parent_id' => 1,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 3,
                    'parent_id' => 1,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 4,
                    'parent_id' => 1,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 5,
                    'parent_id' => 1,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 6,
                    'parent_id' => null,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 7,
                    'parent_id' => 6,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 8,
                    'parent_id' => 6,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 9,
                    'parent_id' => 6,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 10,
                    'parent_id' => 6,
                ],
            ],
        ];
        $expected = [
            [
                'ModelName' => [
                    'id' => 1,
                    'parent_id' => null,
                ],
                'children' => [
                    [
                        'ModelName' => [
                            'id' => 2,
                            'parent_id' => 1,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 3,
                            'parent_id' => 1,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 4,
                            'parent_id' => 1,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 5,
                            'parent_id' => 1,
                        ],
                        'children' => [],
                    ],

                ],
            ],
            [
                'ModelName' => [
                    'id' => 6,
                    'parent_id' => null,
                ],
                'children' => [
                    [
                        'ModelName' => [
                            'id' => 7,
                            'parent_id' => 6,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 8,
                            'parent_id' => 6,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 9,
                            'parent_id' => 6,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 10,
                            'parent_id' => 6,
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ];
        $result = Hash::nest($input);
        $this->assertSame($expected, $result);
    }

    /**
     * test Hash nest with a normal model result set, and a nominated root id
     */
    public function testNestModelExplicitRoot(): void
    {
        $input = [
            [
                'ModelName' => [
                    'id' => 1,
                    'parent_id' => null,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 2,
                    'parent_id' => 1,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 3,
                    'parent_id' => 1,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 4,
                    'parent_id' => 1,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 5,
                    'parent_id' => 1,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 6,
                    'parent_id' => null,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 7,
                    'parent_id' => 6,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 8,
                    'parent_id' => 6,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 9,
                    'parent_id' => 6,
                ],
            ],
            [
                'ModelName' => [
                    'id' => 10,
                    'parent_id' => 6,
                ],
            ],
        ];
        $expected = [
            [
                'ModelName' => [
                    'id' => 6,
                    'parent_id' => null,
                ],
                'children' => [
                    [
                        'ModelName' => [
                            'id' => 7,
                            'parent_id' => 6,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 8,
                            'parent_id' => 6,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 9,
                            'parent_id' => 6,
                        ],
                        'children' => [],
                    ],
                    [
                        'ModelName' => [
                            'id' => 10,
                            'parent_id' => 6,
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ];
        $result = Hash::nest($input, ['root' => 6]);
        $this->assertSame($expected, $result);
    }

    /**
     * test Hash nest with a 1d array - this method should be able to handle any type of array input
     */
    public function testNest1Dimensional(): void
    {
        $input = [
            [
                'id' => 1,
                'parent_id' => null,
            ],
            [
                'id' => 2,
                'parent_id' => 1,
            ],
            [
                'id' => 3,
                'parent_id' => 1,
            ],
            [
                'id' => 4,
                'parent_id' => 1,
            ],
            [
                'id' => 5,
                'parent_id' => 1,
            ],
            [
                'id' => 6,
                'parent_id' => null,
            ],
            [
                'id' => 7,
                'parent_id' => 6,
            ],
            [
                'id' => 8,
                'parent_id' => 6,
            ],
            [
                'id' => 9,
                'parent_id' => 6,
            ],
            [
                'id' => 10,
                'parent_id' => 6,
            ],
        ];
        $expected = [
            [
                'id' => 1,
                'parent_id' => null,
                'children' => [
                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'children' => [],
                    ],
                    [
                        'id' => 3,
                        'parent_id' => 1,
                        'children' => [],
                    ],
                    [
                        'id' => 4,
                        'parent_id' => 1,
                        'children' => [],
                    ],
                    [
                        'id' => 5,
                        'parent_id' => 1,
                        'children' => [],
                    ],

                ],
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'children' => [
                    [
                        'id' => 7,
                        'parent_id' => 6,
                        'children' => [],
                    ],
                    [
                        'id' => 8,
                        'parent_id' => 6,
                        'children' => [],
                    ],
                    [
                        'id' => 9,
                        'parent_id' => 6,
                        'children' => [],
                    ],
                    [
                        'id' => 10,
                        'parent_id' => 6,
                        'children' => [],
                    ],
                ],
            ],
        ];
        $result = Hash::nest($input, ['idPath' => '{n}.id', 'parentPath' => '{n}.parent_id']);
        $this->assertSame($expected, $result);
    }

    /**
     * test Hash nest with no specified parent data.
     *
     * The result should be the same as the input.
     * For an easier comparison, unset all the empty children arrays from the result
     */
    public function testMissingParent(): void
    {
        $input = [
            [
                'id' => 1,
            ],
            [
                'id' => 2,
            ],
            [
                'id' => 3,
            ],
            [
                'id' => 4,
            ],
            [
                'id' => 5,
            ],
            [
                'id' => 6,
            ],
            [
                'id' => 7,
            ],
            [
                'id' => 8,
            ],
            [
                'id' => 9,
            ],
            [
                'id' => 10,
            ],
        ];

        $result = Hash::nest($input, ['idPath' => '{n}.id', 'parentPath' => '{n}.parent_id']);
        foreach ($result as &$row) {
            if (empty($row['children'])) {
                unset($row['children']);
            }
        }
        $this->assertSame($input, $result);
    }

    /**
     * Tests that nest() throws an InvalidArgumentException when providing an invalid input.
     */
    public function testNestInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $input = [
            [
                'ParentCategory' => [
                    'id' => '1',
                    'name' => 'Lorem ipsum dolor sit amet',
                    'parent_id' => '1',
                ],
            ],
        ];
        Hash::nest($input);
    }

    /**
     * testMergeDiff method
     */
    public function testMergeDiff(): void
    {
        $first = [
            'ModelOne' => [
                'id' => 1001,
                'field_one' => 'a1.m1.f1',
                'field_two' => 'a1.m1.f2',
            ],
        ];
        $second = [
            'ModelTwo' => [
                'id' => 1002,
                'field_one' => 'a2.m2.f1',
                'field_two' => 'a2.m2.f2',
            ],
        ];
        $result = Hash::mergeDiff($first, $second);
        $this->assertSame($result, $first + $second);

        $result = Hash::mergeDiff($first, []);
        $this->assertSame($result, $first);

        $result = Hash::mergeDiff([], $first);
        $this->assertSame($result, $first);

        $third = [
            'ModelOne' => [
                'id' => 1003,
                'field_one' => 'a3.m1.f1',
                'field_two' => 'a3.m1.f2',
                'field_three' => 'a3.m1.f3',
            ],
        ];
        $result = Hash::mergeDiff($first, $third);
        $expected = [
            'ModelOne' => [
                'id' => 1001,
                'field_one' => 'a1.m1.f1',
                'field_two' => 'a1.m1.f2',
                'field_three' => 'a3.m1.f3',
            ],
        ];
        $this->assertSame($expected, $result);

        $first = [
            0 => ['ModelOne' => ['id' => 1001, 'field_one' => 's1.0.m1.f1', 'field_two' => 's1.0.m1.f2']],
            1 => ['ModelTwo' => ['id' => 1002, 'field_one' => 's1.1.m2.f2', 'field_two' => 's1.1.m2.f2']],
        ];
        $second = [
            0 => ['ModelOne' => ['id' => 1001, 'field_one' => 's2.0.m1.f1', 'field_two' => 's2.0.m1.f2']],
            1 => ['ModelTwo' => ['id' => 1002, 'field_one' => 's2.1.m2.f2', 'field_two' => 's2.1.m2.f2']],
        ];

        $result = Hash::mergeDiff($first, $second);
        $this->assertSame($result, $first);

        $third = [
            0 => [
                'ModelThree' => [
                    'id' => 1003,
                    'field_one' => 's3.0.m3.f1',
                    'field_two' => 's3.0.m3.f2',
                ],
            ],
        ];

        $result = Hash::mergeDiff($first, $third);
        $expected = [
            0 => [
                'ModelOne' => [
                    'id' => 1001,
                    'field_one' => 's1.0.m1.f1',
                    'field_two' => 's1.0.m1.f2',
                ],
                'ModelThree' => [
                    'id' => 1003,
                    'field_one' => 's3.0.m3.f1',
                    'field_two' => 's3.0.m3.f2',
                ],
            ],
            1 => [
                'ModelTwo' => [
                    'id' => 1002,
                    'field_one' => 's1.1.m2.f2',
                    'field_two' => 's1.1.m2.f2',
                ],
            ],
        ];
        $this->assertSame($expected, $result);

        $result = Hash::mergeDiff($first, []);
        $this->assertSame($result, $first);

        $result = Hash::mergeDiff($first, $second);
        $this->assertSame($result, $first + $second);
    }

    /**
     * Test mergeDiff() with scalar elements.
     */
    public function testMergeDiffWithScalarValue(): void
    {
        $result = Hash::mergeDiff(['a' => 'value'], ['a' => ['value']]);
        $this->assertSame(['a' => 'value'], $result);

        $result = Hash::mergeDiff(['a' => ['value']], ['a' => 'value']);
        $this->assertSame(['a' => ['value']], $result);
    }

    /**
     * Tests Hash::expand
     */
    public function testExpand(): void
    {
        $data = ['My', 'Array', 'To', 'Flatten'];
        $flat = Hash::flatten($data);
        $result = Hash::expand($flat);
        $this->assertSame($data, $result);

        $data = [
            '0.Post.id' => '1', '0.Post.author_id' => '1', '0.Post.title' => 'First Post', '0.Author.id' => '1',
            '0.Author.user' => 'nate', '0.Author.password' => 'foo', '1.Post.id' => '2', '1.Post.author_id' => '3',
            '1.Post.title' => 'Second Post', '1.Post.body' => 'Second Post Body', '1.Author.id' => '3',
            '1.Author.user' => 'larry', '1.Author.password' => null,
        ];
        $result = Hash::expand($data);
        $expected = [
            [
                'Post' => ['id' => '1', 'author_id' => '1', 'title' => 'First Post'],
                'Author' => ['id' => '1', 'user' => 'nate', 'password' => 'foo'],
            ],
            [
                'Post' => ['id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body'],
                'Author' => ['id' => '3', 'user' => 'larry', 'password' => null],
            ],
        ];
        $this->assertSame($expected, $result);

        $data = [
            '0/Post/id' => 1,
            '0/Post/name' => 'test post',
        ];
        $result = Hash::expand($data, '/');
        $expected = [
            [
                'Post' => [
                    'id' => 1,
                    'name' => 'test post',
                ],
            ],
        ];
        $this->assertSame($expected, $result);

        $data = ['a.b.100.a' => null, 'a.b.200.a' => null];
        $expected = [
            'a' => [
                'b' => [
                    100 => ['a' => null],
                    200 => ['a' => null],
                ],
            ],
        ];
        $result = Hash::expand($data);
        $this->assertSame($expected, $result);
    }

    /**
     * Test that flattening a large complex set doesn't loop forever.
     */
    public function testFlattenInfiniteLoop(): void
    {
        $data = [
            'Order.ASI' => '0',
            'Order.Accounting' => '0',
            'Order.Admin' => '0',
            'Order.Art' => '0',
            'Order.ArtChecker' => '0',
            'Order.Canned' => '0',
            'Order.Customer_Tags' => '',
            'Order.Embroidery' => '0',
            'Order.Item.0.Product.style_number' => 'a11222',
            'Order.Item.0.Product.slug' => 'a11222',
            'Order.Item.0.Product._id' => '4ff8b8d3d7bbe8ad30000000',
            'Order.Item.0.Product.Color.slug' => 'kelly_green',
            'Order.Item.0.Product.ColorSizes.0.Color.color' => 'Sport Grey',
            'Order.Item.0.Product.ColorSizes.0.Color.slug' => 'sport_grey',
            'Order.Item.0.Product.ColorSizes.1.Color.color' => 'Kelly Green',
            'Order.Item.0.Product.ColorSizes.1.Color.slug' => 'kelly_green',
            'Order.Item.0.Product.ColorSizes.2.Color.color' => 'Orange',
            'Order.Item.0.Product.ColorSizes.2.Color.slug' => 'orange',
            'Order.Item.0.Product.ColorSizes.3.Color.color' => 'Yellow Haze',
            'Order.Item.0.Product.ColorSizes.3.Color.slug' => 'yellow_haze',
            'Order.Item.0.Product.brand' => 'OUTER BANKS',
            'Order.Item.0.Product.style' => 'T-shirt',
            'Order.Item.0.Product.description' => 'uhiuhuih oin ooi ioo ioio',
            'Order.Item.0.Product.sizes.0.Size.qty' => '',
            'Order.Item.0.Product.sizes.0.Size.size' => '0-3mo',
            'Order.Item.0.Product.sizes.0.Size.id' => '38',
            'Order.Item.0.Product.sizes.1.Size.qty' => '',
            'Order.Item.0.Product.sizes.1.Size.size' => '3-6mo',
            'Order.Item.0.Product.sizes.1.Size.id' => '39',
            'Order.Item.0.Product.sizes.2.Size.qty' => '78',
            'Order.Item.0.Product.sizes.2.Size.size' => '6-9mo',
            'Order.Item.0.Product.sizes.2.Size.id' => '40',
            'Order.Item.0.Product.sizes.3.Size.qty' => '',
            'Order.Item.0.Product.sizes.3.Size.size' => '6-12mo',
            'Order.Item.0.Product.sizes.3.Size.id' => '41',
            'Order.Item.0.Product.sizes.4.Size.qty' => '',
            'Order.Item.0.Product.sizes.4.Size.size' => '12-18mo',
            'Order.Item.0.Product.sizes.4.Size.id' => '42',
            'Order.Item.0.Art.imprint_locations.0.id' => 2,
            'Order.Item.0.Art.imprint_locations.0.name' => 'Left Chest',
            'Order.Item.0.Art.imprint_locations.0.imprint_type.id' => 7,
            'Order.Item.0.Art.imprint_locations.0.imprint_type.type' => 'Embroidery',
            'Order.Item.0.Art.imprint_locations.0.art' => '',
            'Order.Item.0.Art.imprint_locations.0.num_colors' => 3,
            'Order.Item.0.Art.imprint_locations.0.description' => 'Wooo! This is Embroidery!!',
            'Order.Item.0.Art.imprint_locations.0.lines.0' => 'Platen',
            'Order.Item.0.Art.imprint_locations.0.lines.1' => 'Logo',
            'Order.Item.0.Art.imprint_locations.0.height' => 4,
            'Order.Item.0.Art.imprint_locations.0.width' => 5,
            'Order.Item.0.Art.imprint_locations.0.stitch_density' => 'Light',
            'Order.Item.0.Art.imprint_locations.0.metallic_thread' => true,
            'Order.Item.0.Art.imprint_locations.1.id' => 4,
            'Order.Item.0.Art.imprint_locations.1.name' => 'Full Back',
            'Order.Item.0.Art.imprint_locations.1.imprint_type.id' => 6,
            'Order.Item.0.Art.imprint_locations.1.imprint_type.type' => 'Screenprinting',
            'Order.Item.0.Art.imprint_locations.1.art' => '',
            'Order.Item.0.Art.imprint_locations.1.num_colors' => 3,
            'Order.Item.0.Art.imprint_locations.1.description' => 'Wooo! This is Screenprinting!!',
            'Order.Item.0.Art.imprint_locations.1.lines.0' => 'Platen',
            'Order.Item.0.Art.imprint_locations.1.lines.1' => 'Logo',
            'Order.Item.0.Art.imprint_locations.2.id' => 26,
            'Order.Item.0.Art.imprint_locations.2.name' => 'HS - JSY Name Below',
            'Order.Item.0.Art.imprint_locations.2.imprint_type.id' => 9,
            'Order.Item.0.Art.imprint_locations.2.imprint_type.type' => 'Names',
            'Order.Item.0.Art.imprint_locations.2.description' => 'Wooo! This is Names!!',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.0.active' => 1,
            'Order.Item.0.Art.imprint_locations.2.sizes.S.0.name' => 'Benjamin Talavera',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.0.color' => 'Red',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.0.height' => '3',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.0.layout' => 'Arched',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.0.style' => 'Classic',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.1.active' => 0,
            'Order.Item.0.Art.imprint_locations.2.sizes.S.1.name' => 'Rishi Narayan',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.1.color' => 'Cardinal',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.1.height' => '4',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.1.layout' => 'Straight',
            'Order.Item.0.Art.imprint_locations.2.sizes.S.1.style' => 'Team US',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.0.active' => 1,
            'Order.Item.0.Art.imprint_locations.2.sizes.M.0.name' => 'Brandon Plasters',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.0.color' => 'Red',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.0.height' => '3',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.0.layout' => 'Arched',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.0.style' => 'Classic',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.1.active' => 0,
            'Order.Item.0.Art.imprint_locations.2.sizes.M.1.name' => 'Andrew Reed',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.1.color' => 'Cardinal',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.1.height' => '4',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.1.layout' => 'Straight',
            'Order.Item.0.Art.imprint_locations.2.sizes.M.1.style' => 'Team US',
            'Order.Job.0._id' => 'job-1',
            'Order.Job.0.type' => 'screenprinting',
            'Order.Job.0.postPress' => 'job-2',
            'Order.Job.1._id' => 'job-2',
            'Order.Job.1.type' => 'embroidery',
            'Order.Postpress' => '0',
            'Order.PriceAdjustment.0._id' => 'price-adjustment-1',
            'Order.PriceAdjustment.0.adjustment' => '-20',
            'Order.PriceAdjustment.0.adjustment_type' => 'percent',
            'Order.PriceAdjustment.0.type' => 'grand_total',
            'Order.PriceAdjustment.1.adjustment' => '20',
            'Order.PriceAdjustment.1.adjustment_type' => 'flat',
            'Order.PriceAdjustment.1.min-items' => '10',
            'Order.PriceAdjustment.1.type' => 'min-items',
            'Order.PriceAdjustment.1._id' => 'another-test-adjustment',
            'Order.Purchasing' => '0',
            'Order.QualityControl' => '0',
            'Order.Receiving' => '0',
            'Order.ScreenPrinting' => '0',
            'Order.Stage.art_approval' => 0,
            'Order.Stage.draft' => 1,
            'Order.Stage.quote' => 1,
            'Order.Stage.order' => 1,
            'Order.StoreLiason' => '0',
            'Order.Tag_UI_Email' => '',
            'Order.Tags' => '',
            'Order._id' => 'test-2',
            'Order.add_print_location' => '',
            'Order.created' => '2011-Dec-29 05:40:18',
            'Order.force_admin' => '0',
            'Order.modified' => '2012-Jul-25 01:24:49',
            'Order.name' => 'towering power',
            'Order.order_id' => '135961',
            'Order.slug' => 'test-2',
            'Order.title' => 'test job 2',
            'Order.type' => 'ttt',
        ];
        $expanded = Hash::expand($data);
        $flattened = Hash::flatten($expanded);
        $this->assertSame($data, $flattened);
    }
}
