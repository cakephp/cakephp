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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

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
    public static function articleData()
    {
        return [
            [
                'Article' => [
                    'id' => '1',
                    'user_id' => '1',
                    'title' => 'First Article',
                    'body' => 'First Article Body'
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
                    ]
                ],
                'Deep' => [
                    'Nesting' => [
                        'test' => [
                            1 => 'foo',
                            2 => [
                                'and' => ['more' => 'stuff']
                            ]
                        ]
                    ]
                ]
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
                'Tag' => []
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
                'Tag' => []
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
                'Tag' => []
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
                'Tag' => []
            ]
        ];
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function articleDataObject()
    {
        return new ArrayObject([
            new Entity([
                'Article' => new ArrayObject([
                    'id' => '1',
                    'user_id' => '1',
                    'title' => 'First Article',
                    'body' => 'First Article Body'
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
                    ])
                ]),
                'Deep' => new ArrayObject([
                    'Nesting' => new ArrayObject([
                        'test' => new ArrayObject([
                            1 => 'foo',
                            2 => new ArrayObject([
                                'and' => new ArrayObject(['more' => 'stuff'])
                            ])
                        ])
                    ])
                ])
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
                'Tag' => new ArrayObject([])
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
                'Tag' => new ArrayObject([])
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
                'Tag' => new ArrayObject([])
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
                'Tag' => new ArrayObject([])
            ])
        ]);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function articleDataSets()
    {
        return [
            [static::articleData()],
            [static::articleDataObject()]
        ];
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function userData()
    {
        return [
            [
                'User' => [
                    'id' => 2,
                    'group_id' => 1,
                    'Data' => [
                        'user' => 'mariano.iglesias',
                        'name' => 'Mariano Iglesias'
                    ]
                ]
            ],
            [
                'User' => [
                    'id' => 14,
                    'group_id' => 2,
                    'Data' => [
                        'user' => 'phpnut',
                        'name' => 'Larry E. Masters'
                    ]
                ]
            ],
            [
                'User' => [
                    'id' => 25,
                    'group_id' => 1,
                    'Data' => [
                        'user' => 'gwoo',
                        'name' => 'The Gwoo'
                    ]
                ]
            ]
        ];
    }

    /**
     * Test get()
     *
     * @return void
     */
    public function testGet()
    {
        $data = ['abc', 'def'];

        $result = Hash::get($data, '0');
        $this->assertEquals('abc', $result);

        $result = Hash::get($data, 0);
        $this->assertEquals('abc', $result);

        $result = Hash::get($data, '1');
        $this->assertEquals('def', $result);

        $data = self::articleData();

        $result = Hash::get([], '1.Article.title');
        $this->assertNull($result);

        $result = Hash::get($data, '');
        $this->assertNull($result);

        $result = Hash::get($data, null, '-');
        $this->assertSame('-', $result);

        $result = Hash::get($data, '0.Article.title');
        $this->assertEquals('First Article', $result);

        $result = Hash::get($data, '1.Article.title');
        $this->assertEquals('Second Article', $result);

        $result = Hash::get($data, '5.Article.title');
        $this->assertNull($result);

        $default = ['empty'];
        $this->assertEquals($default, Hash::get($data, '5.Article.title', $default));
        $this->assertEquals($default, Hash::get([], '5.Article.title', $default));

        $result = Hash::get($data, '1.Article.title.not_there');
        $this->assertNull($result);

        $result = Hash::get($data, '1.Article');
        $this->assertEquals($data[1]['Article'], $result);

        $result = Hash::get($data, ['1', 'Article']);
        $this->assertEquals($data[1]['Article'], $result);

        // Object which implements ArrayAccess
        $nested = new ArrayObject([
            'user' => 'bar'
        ]);
        $data = new ArrayObject([
            'name' => 'foo',
            'associated' => $nested
        ]);

        $return = Hash::get($data, 'name');
        $this->assertEquals('foo', $return);

        $return = Hash::get($data, 'associated');
        $this->assertEquals($nested, $return);

        $return = Hash::get($data, 'associated.user');
        $this->assertEquals('bar', $return);

        $return = Hash::get($data, 'non-existent');
        $this->assertNull($return);

        $data = ['a' => ['b' => ['c' => ['d' => 1]]]];
        $this->assertEquals(1, Hash::get(new ArrayObject($data), 'a.b.c.d'));
    }

    /**
     * Test get() for invalid $data type
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid data type, must be an array or \ArrayAccess instance.
     * @return void
     */
    public function testGetInvalidData()
    {
        Hash::get('string', 'path');
    }

    /**
     * Test get() with an invalid path
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testGetInvalidPath()
    {
        Hash::get(['one' => 'two'], true);
    }

    /**
     * Test dimensions.
     *
     * @return void
     */
    public function testDimensions()
    {
        $result = Hash::dimensions([]);
        $this->assertEquals($result, 0);

        $data = ['one', '2', 'three'];
        $result = Hash::dimensions($data);
        $this->assertEquals($result, 1);

        $data = ['1' => '1.1', '2', '3'];
        $result = Hash::dimensions($data);
        $this->assertEquals($result, 1);

        $data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => '3.1.1']];
        $result = Hash::dimensions($data);
        $this->assertEquals($result, 2);

        $data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
        $result = Hash::dimensions($data);
        $this->assertEquals($result, 1);

        $data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
        $result = Hash::dimensions($data);
        $this->assertEquals($result, 2);
    }

    /**
     * Test maxDimensions
     *
     * @return void
     */
    public function testMaxDimensions()
    {
        $data = [];
        $result = Hash::maxDimensions($data);
        $this->assertEquals(0, $result);

        $data = ['a', 'b'];
        $result = Hash::maxDimensions($data);
        $this->assertEquals(1, $result);

        $data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
        $result = Hash::maxDimensions($data);
        $this->assertEquals($result, 2);

        $data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
        $result = Hash::maxDimensions($data);
        $this->assertEquals($result, 3);

        $data = [
            '1' => ['1.1' => '1.1.1'],
            ['2' => ['2.1' => ['2.1.1' => '2.1.1.1']]],
            '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
        ];
        $result = Hash::maxDimensions($data);
        $this->assertEquals($result, 4);

        $data = [
           '1' => [
               '1.1' => '1.1.1',
               '1.2' => [
                   '1.2.1' => [
                       '1.2.1.1',
                       ['1.2.2.1']
                   ]
               ]
           ],
           '2' => ['2.1' => '2.1.1']
        ];
        $result = Hash::maxDimensions($data);
        $this->assertEquals($result, 5);
    }

    /**
     * Tests Hash::flatten
     *
     * @return void
     */
    public function testFlatten()
    {
        $data = ['Larry', 'Curly', 'Moe'];
        $result = Hash::flatten($data);
        $this->assertEquals($result, $data);

        $data[9] = 'Shemp';
        $result = Hash::flatten($data);
        $this->assertEquals($result, $data);

        $data = [
            [
                'Post' => ['id' => '1', 'author_id' => '1', 'title' => 'First Post'],
                'Author' => ['id' => '1', 'user' => 'nate', 'password' => 'foo'],
            ],
            [
                'Post' => ['id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body'],
                'Author' => ['id' => '3', 'user' => 'larry', 'password' => null],
            ]
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
            '1.Author.password' => null
        ];
        $this->assertEquals($expected, $result);

        $data = [
            [
                'Post' => ['id' => '1', 'author_id' => null, 'title' => 'First Post'],
                'Author' => [],
            ]
        ];
        $result = Hash::flatten($data);
        $expected = [
            '0.Post.id' => '1',
            '0.Post.author_id' => null,
            '0.Post.title' => 'First Post',
            '0.Author' => []
        ];
        $this->assertEquals($expected, $result);

        $data = [
            ['Post' => ['id' => 1]],
            ['Post' => ['id' => 2]],
        ];
        $result = Hash::flatten($data, '/');
        $expected = [
            '0/Post/id' => '1',
            '1/Post/id' => '2',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test diff();
     *
     * @return void
     */
    public function testDiff()
    {
        $a = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about']
        ];
        $b = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about'],
            2 => ['name' => 'contact']
        ];

        $result = Hash::diff($a, []);
        $expected = $a;
        $this->assertEquals($expected, $result);

        $result = Hash::diff([], $b);
        $expected = $b;
        $this->assertEquals($expected, $result);

        $result = Hash::diff($a, $b);
        $expected = [
            2 => ['name' => 'contact']
        ];
        $this->assertEquals($expected, $result);

        $b = [
            0 => ['name' => 'me'],
            1 => ['name' => 'about']
        ];

        $result = Hash::diff($a, $b);
        $expected = [
            0 => ['name' => 'main']
        ];
        $this->assertEquals($expected, $result);

        $a = [];
        $b = ['name' => 'bob', 'address' => 'home'];
        $result = Hash::diff($a, $b);
        $this->assertEquals($result, $b);

        $a = ['name' => 'bob', 'address' => 'home'];
        $b = [];
        $result = Hash::diff($a, $b);
        $this->assertEquals($result, $a);

        $a = ['key' => true, 'another' => false, 'name' => 'me'];
        $b = ['key' => 1, 'another' => 0];
        $expected = ['name' => 'me'];
        $result = Hash::diff($a, $b);
        $this->assertEquals($expected, $result);

        $a = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $b = ['key' => 'differentValue', 'another' => null];
        $expected = ['key' => 'value', 'name' => 'me'];
        $result = Hash::diff($a, $b);
        $this->assertEquals($expected, $result);

        $a = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $b = ['key' => 'differentValue', 'another' => 'value'];
        $expected = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $result = Hash::diff($a, $b);
        $this->assertEquals($expected, $result);

        $a = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $b = ['key' => 'differentValue', 'another' => 'value'];
        $expected = ['key' => 'differentValue', 'another' => 'value', 'name' => 'me'];
        $result = Hash::diff($b, $a);
        $this->assertEquals($expected, $result);

        $a = ['key' => 'value', 'another' => null, 'name' => 'me'];
        $b = [0 => 'differentValue', 1 => 'value'];
        $expected = $a + $b;
        $result = Hash::diff($a, $b);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test merge()
     *
     * @return void
     */
    public function testMerge()
    {
        $result = Hash::merge(['foo'], ['bar']);
        $this->assertEquals($result, ['foo', 'bar']);

        $a = ['foo', 'foo2'];
        $b = ['bar', 'bar2'];
        $expected = ['foo', 'foo2', 'bar', 'bar2'];
        $this->assertEquals($expected, Hash::merge($a, $b));

        $a = ['foo' => 'bar', 'bar' => 'foo'];
        $b = ['foo' => 'no-bar', 'bar' => 'no-foo'];
        $expected = ['foo' => 'no-bar', 'bar' => 'no-foo'];
        $this->assertEquals($expected, Hash::merge($a, $b));

        $a = ['users' => ['bob', 'jim']];
        $b = ['users' => ['lisa', 'tina']];
        $expected = ['users' => ['bob', 'jim', 'lisa', 'tina']];
        $this->assertEquals($expected, Hash::merge($a, $b));

        $a = ['users' => ['jim', 'bob']];
        $b = ['users' => 'none'];
        $expected = ['users' => 'none'];
        $this->assertEquals($expected, Hash::merge($a, $b));

        $a = [
            'Tree',
            'CounterCache',
            'Upload' => [
                'folder' => 'products',
                'fields' => ['image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id']
            ]
        ];
        $b = [
            'Cacheable' => ['enabled' => false],
            'Limit',
            'Bindable',
            'Validator',
            'Transactional'
        ];
        $expected = [
            'Tree',
            'CounterCache',
            'Upload' => [
                'folder' => 'products',
                'fields' => ['image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id']
            ],
            'Cacheable' => ['enabled' => false],
            'Limit',
            'Bindable',
            'Validator',
            'Transactional'
        ];
        $this->assertEquals($expected, Hash::merge($a, $b));
    }

    /**
     * Test that merge() works with variadic arguments.
     *
     * @return void
     */
    public function testMergeVariadic()
    {
        $result = Hash::merge(
            ['hkuc' => ['lion']],
            ['hkuc' => 'lion']
        );
        $expected = ['hkuc' => 'lion'];
        $this->assertEquals($expected, $result);

        $result = Hash::merge(
            ['hkuc' => ['lion']],
            ['hkuc' => ['lion']],
            ['hkuc' => 'lion']
        );
        $this->assertEquals($expected, $result);

        $result = Hash::merge(['foo'], ['user' => 'bob', 'no-bar'], 'bar');
        $this->assertEquals($result, ['foo', 'user' => 'bob', 'no-bar', 'bar']);

        $a = ['users' => ['lisa' => ['id' => 5, 'pw' => 'secret']], 'cakephp'];
        $b = ['users' => ['lisa' => ['pw' => 'new-pass', 'age' => 23]], 'ice-cream'];
        $expected = [
            'users' => ['lisa' => ['id' => 5, 'pw' => 'new-pass', 'age' => 23]],
            'cakephp',
            'ice-cream'
        ];
        $result = Hash::merge($a, $b);
        $this->assertEquals($expected, $result);

        $c = [
            'users' => ['lisa' => ['pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog']],
            'chocolate'
        ];
        $expected = [
            'users' => ['lisa' => ['id' => 5, 'pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog']],
            'cakephp',
            'ice-cream',
            'chocolate'
        ];
        $this->assertEquals($expected, Hash::merge($a, $b, $c));
        $this->assertEquals($expected, Hash::merge($a, $b, [], $c));
    }

    /**
     * test normalizing arrays
     *
     * @return void
     */
    public function testNormalize()
    {
        $result = Hash::normalize(['one', 'two', 'three']);
        $expected = ['one' => null, 'two' => null, 'three' => null];
        $this->assertEquals($expected, $result);

        $result = Hash::normalize(['one', 'two', 'three'], false);
        $expected = ['one', 'two', 'three'];
        $this->assertEquals($expected, $result);

        $result = Hash::normalize(['one' => 1, 'two' => 2, 'three' => 3, 'four'], false);
        $expected = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => null];
        $this->assertEquals($expected, $result);

        $result = Hash::normalize(['one' => 1, 'two' => 2, 'three' => 3, 'four']);
        $expected = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => null];
        $this->assertEquals($expected, $result);

        $result = Hash::normalize(['one' => ['a', 'b', 'c' => 'cee'], 'two' => 2, 'three']);
        $expected = ['one' => ['a', 'b', 'c' => 'cee'], 'two' => 2, 'three' => null];
        $this->assertEquals($expected, $result);
    }

    /**
     * testContains method
     *
     * @return void
     */
    public function testContains()
    {
        $data = ['apple', 'bee', 'cyclops'];
        $this->assertTrue(Hash::contains($data, ['apple']));
        $this->assertFalse(Hash::contains($data, ['data']));

        $a = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about']
        ];
        $b = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about'],
            2 => ['name' => 'contact'],
            'a' => 'b'
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
            ['User' => ['id' => 3]]
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
     *
     * @return void
     */
    public function testFilter()
    {
        $result = Hash::filter(['0', false, true, 0, ['one thing', 'I can tell you', 'is you got to be', false]]);
        $expected = ['0', 2 => true, 3 => 0, 4 => ['one thing', 'I can tell you', 'is you got to be']];
        $this->assertSame($expected, $result);

        $result = Hash::filter([1, [false]]);
        $expected = [1];
        $this->assertEquals($expected, $result);

        $result = Hash::filter([1, [false, false]]);
        $expected = [1];
        $this->assertEquals($expected, $result);

        $result = Hash::filter([1, ['empty', false]]);
        $expected = [1, ['empty']];
        $this->assertEquals($expected, $result);

        $result = Hash::filter([1, ['2', false, [3, null]]]);
        $expected = [1, ['2', 2 => [3]]];
        $this->assertEquals($expected, $result);

        $this->assertSame([], Hash::filter([]));
    }

    /**
     * testNumericArrayCheck method
     *
     * @return void
     */
    public function testNumeric()
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
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid data type, must be an array or \ArrayAccess instance.
     * @return void
     */
    public function testExtractInvalidArgument()
    {
        Hash::extract('foo', '');
    }

    /**
     * Test the extraction of a single value filtered by another field.
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractSingleValueWithFilteringByAnotherField($data)
    {
        $result = Hash::extract($data, '{*}.Article[id=1].title');
        $this->assertEquals([0 => 'First Article'], $result);

        $result = Hash::extract($data, '{*}.Article[id=2].title');
        $this->assertEquals([0 => 'Second Article'], $result);
    }

    /**
     * Test simple paths.
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractBasic($data)
    {
        $result = Hash::extract($data, '');
        $this->assertEquals($data, $result);

        $result = Hash::extract($data, '0.Article.title');
        $this->assertEquals(['First Article'], $result);

        $result = Hash::extract($data, '1.Article.title');
        $this->assertEquals(['Second Article'], $result);

        $result = Hash::extract([false], '{n}.Something.another_thing');
        $this->assertEquals([], $result);
    }

    /**
     * Test the {n} selector
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractNumericKey($data)
    {
        $result = Hash::extract($data, '{n}.Article.title');
        $expected = [
            'First Article', 'Second Article',
            'Third Article', 'Fourth Article',
            'Fifth Article'
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($data, '0.Comment.{n}.user_id');
        $expected = [
            '2', '4'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the {n} selector with inconsistent arrays
     *
     * @return void
     */
    public function testExtractNumericMixedKeys()
    {
        $data = [
            'User' => [
                0 => [
                    'id' => 4,
                    'name' => 'Neo'
                ],
                1 => [
                    'id' => 5,
                    'name' => 'Morpheus'
                ],
                'stringKey' => [
                    'name' => 'Fail'
                ]
            ]
        ];
        $result = Hash::extract($data, 'User.{n}.name');
        $expected = ['Neo', 'Morpheus'];
        $this->assertEquals($expected, $result);

        $data = new ArrayObject([
            'User' => new ArrayObject([
                0 => new Entity([
                    'id' => 4,
                    'name' => 'Neo'
                ]),
                1 => new ArrayObject([
                    'id' => 5,
                    'name' => 'Morpheus'
                ]),
                'stringKey' => new ArrayObject([
                    'name' => 'Fail'
                ])
            ])
        ]);
        $result = Hash::extract($data, 'User.{n}.name');
        $this->assertEquals($expected, $result);

        $data = [
            0 => new Entity([
                'id' => 4,
                'name' => 'Neo'
            ]),
            'stringKey' => new ArrayObject([
                'name' => 'Fail'
            ])
        ];
        $result = Hash::extract($data, '{n}.name');
        $expected = ['Neo'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the {n} selector with non-zero based arrays
     *
     * @return void
     */
    public function testExtractNumericNonZero()
    {
        $data = [
            1 => [
                'User' => [
                    'id' => 1,
                    'name' => 'John',
                ]
            ],
            2 => [
                'User' => [
                    'id' => 2,
                    'name' => 'Bob',
                ]
            ],
            3 => [
                'User' => [
                    'id' => 3,
                    'name' => 'Tony',
                ]
            ]
        ];
        $result = Hash::extract($data, '{n}.User.name');
        $expected = ['John', 'Bob', 'Tony'];
        $this->assertEquals($expected, $result);

        $data = new ArrayObject([
            1 => new ArrayObject([
                'User' => new ArrayObject([
                    'id' => 1,
                    'name' => 'John',
                ])
            ]),
            2 => new ArrayObject([
                'User' => new ArrayObject([
                    'id' => 2,
                    'name' => 'Bob',
                ])
            ]),
            3 => new ArrayObject([
                'User' => new ArrayObject([
                    'id' => 3,
                    'name' => 'Tony',
                ])
            ])
        ]);
        $result = Hash::extract($data, '{n}.User.name');
        $expected = ['John', 'Bob', 'Tony'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the {s} selector.
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractStringKey($data)
    {
        $result = Hash::extract($data, '{n}.{s}.user');
        $expected = [
            'mariano',
            'mariano',
            'mariano',
            'mariano',
            'mariano'
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($data, '{n}.{s}.Nesting.test.1');
        $this->assertEquals(['foo'], $result);
    }

    /**
     * Test wildcard matcher
     *
     * @return void
     */
    public function testExtractWildcard()
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
        $this->assertEquals($expected, $result);

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
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the attribute presense selector.
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractAttributePresence($data)
    {
        $result = Hash::extract($data, '{n}.Article[published]');
        $expected = [$data[1]['Article']];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($data, '{n}.Article[id][published]');
        $expected = [$data[1]['Article']];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test = and != operators.
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractAttributeEquality($data)
    {
        $result = Hash::extract($data, '{n}.Article[id=3]');
        $expected = [$data[2]['Article']];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($data, '{n}.Article[id = 3]');
        $expected = [$data[2]['Article']];
        $this->assertEquals($expected, $result, 'Whitespace should not matter.');

        $result = Hash::extract($data, '{n}.Article[id!=3]');
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[1]['id']);
        $this->assertEquals(4, $result[2]['id']);
        $this->assertEquals(5, $result[3]['id']);
    }

    /**
     * Test extracting based on attributes with boolean values.
     *
     * @return void
     */
    public function testExtractAttributeBoolean()
    {
        $usersArray = [
            [
                'id' => 2,
                'username' => 'johndoe',
                'active' => true
            ],
            [
                'id' => 5,
                'username' => 'kevin',
                'active' => true
            ],
            [
                'id' => 9,
                'username' => 'samantha',
                'active' => false
            ],
        ];

        $usersObject = new ArrayObject([
            new ArrayObject([
                'id' => 2,
                'username' => 'johndoe',
                'active' => true
            ]),
            new ArrayObject([
                'id' => 5,
                'username' => 'kevin',
                'active' => true
            ]),
            new ArrayObject([
                'id' => 9,
                'username' => 'samantha',
                'active' => false
            ]),
        ]);

        foreach ([$usersArray, $usersObject] as $users) {
            $result = Hash::extract($users, '{n}[active=0]');
            $this->assertCount(1, $result);
            $this->assertEquals($users[2], $result[0]);

            $result = Hash::extract($users, '{n}[active=false]');
            $this->assertCount(1, $result);
            $this->assertEquals($users[2], $result[0]);

            $result = Hash::extract($users, '{n}[active=1]');
            $this->assertCount(2, $result);
            $this->assertEquals($users[0], $result[0]);
            $this->assertEquals($users[1], $result[1]);

            $result = Hash::extract($users, '{n}[active=true]');
            $this->assertCount(2, $result);
            $this->assertEquals($users[0], $result[0]);
            $this->assertEquals($users[1], $result[1]);
        }
    }

    /**
     * Test that attribute matchers don't cause errors on scalar data.
     *
     * @return void
     */
    public function testExtractAttributeEqualityOnScalarValue()
    {
        $dataArray = [
            'Entity' => [
                'id' => 1,
                'data1' => 'value',
            ]
        ];
        $dataObject = new ArrayObject([
            'Entity' => new ArrayObject([
                'id' => 1,
                'data1' => 'value',
            ])
        ]);

        foreach ([$dataArray, $dataObject] as $data) {
            $result = Hash::extract($data, 'Entity[id=1].data1');
            $this->assertEquals(['value'], $result);

            $data = ['Entity' => false];
            $result = Hash::extract($data, 'Entity[id=1].data1');
            $this->assertEquals([], $result);
        }
    }

    /**
     * Test comparison operators.
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractAttributeComparison($data)
    {
        $result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2]');
        $expected = [$data[0]['Comment'][1]];
        $this->assertEquals($expected, $result);
        $this->assertEquals(4, $expected[0]['user_id']);

        $result = Hash::extract($data, '{n}.Comment.{n}[user_id >= 4]');
        $expected = [$data[0]['Comment'][1]];
        $this->assertEquals($expected, $result);
        $this->assertEquals(4, $expected[0]['user_id']);

        $result = Hash::extract($data, '{n}.Comment.{n}[user_id < 3]');
        $expected = [$data[0]['Comment'][0]];
        $this->assertEquals($expected, $result);
        $this->assertEquals(2, $expected[0]['user_id']);

        $result = Hash::extract($data, '{n}.Comment.{n}[user_id <= 2]');
        $expected = [$data[0]['Comment'][0]];
        $this->assertEquals($expected, $result);
        $this->assertEquals(2, $expected[0]['user_id']);
    }

    /**
     * Test multiple attributes with conditions.
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractAttributeMultiple($data)
    {
        $result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2][id=1]');
        $this->assertEmpty($result);

        $result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2][id=2]');
        $expected = [$data[0]['Comment'][1]];
        $this->assertEquals($expected, $result);
        $this->assertEquals(4, $expected[0]['user_id']);
    }

    /**
     * Test attribute pattern matching.
     *
     * @dataProvider articleDataSets
     * @return void
     */
    public function testExtractAttributePattern($data)
    {
        $result = Hash::extract($data, '{n}.Article[title=/^First/]');
        $expected = [$data[0]['Article']];
        $this->assertEquals($expected, $result);

        $result = Hash::extract($data, '{n}.Article[title=/^Fir[a-z]+/]');
        $expected = [$data[0]['Article']];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that extract() + matching can hit null things.
     *
     * @return void
     */
    public function testExtractMatchesNull()
    {
        $data = [
            'Country' => [
                ['name' => 'Canada'],
                ['name' => 'Australia'],
                ['name' => null],
            ]
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
        $this->assertEquals($expected, $result);

        $data = new ArrayObject([
            'Country' => new ArrayObject([
                ['name' => 'Canada'],
                ['name' => 'Australia'],
                ['name' => null],
            ])
        ]);
        $result = Hash::extract($data, 'Country.{n}[name=/Canada|^$/]');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that uneven keys are handled correctly.
     *
     * @return void
     */
    public function testExtractUnevenKeys()
    {
        $data = [
            'Level1' => [
                'Level2' => ['test1', 'test2'],
                'Level2bis' => ['test3', 'test4']
            ]
        ];
        $this->assertEquals(
            ['test1', 'test2'],
            Hash::extract($data, 'Level1.Level2')
        );
        $this->assertEquals(
            ['test3', 'test4'],
            Hash::extract($data, 'Level1.Level2bis')
        );

        $data = new ArrayObject([
            'Level1' => new ArrayObject([
                'Level2' => ['test1', 'test2'],
                'Level2bis' => ['test3', 'test4']
            ])
        ]);
        $this->assertEquals(
            ['test1', 'test2'],
            Hash::extract($data, 'Level1.Level2')
        );
        $this->assertEquals(
            ['test3', 'test4'],
            Hash::extract($data, 'Level1.Level2bis')
        );

        $data = [
            'Level1' => [
                'Level2bis' => [
                    ['test3', 'test4'],
                    ['test5', 'test6']
                ]
            ]
        ];
        $expected = [
            ['test3', 'test4'],
            ['test5', 'test6']
        ];
        $this->assertEquals($expected, Hash::extract($data, 'Level1.Level2bis'));

        $data['Level1']['Level2'] = ['test1', 'test2'];
        $this->assertEquals($expected, Hash::extract($data, 'Level1.Level2bis'));

        $data = new ArrayObject([
            'Level1' => new ArrayObject([
                'Level2bis' => [
                    ['test3', 'test4'],
                    ['test5', 'test6']
                ]
            ])
        ]);
        $this->assertEquals($expected, Hash::extract($data, 'Level1.Level2bis'));

        $data['Level1']['Level2'] = ['test1', 'test2'];
        $this->assertEquals($expected, Hash::extract($data, 'Level1.Level2bis'));
    }

    /**
     * testSort method
     *
     * @return void
     */
    public function testSort()
    {
        $result = Hash::sort([], '{n}.name');
        $this->assertEquals([], $result);

        $a = [
            0 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']]
            ],
            1 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']]
            ]
        ];
        $b = [
            0 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']]
            ],
            1 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']]
            ]
        ];
        $a = Hash::sort($a, '{n}.Friend.{n}.name');
        $this->assertEquals($a, $b);

        $b = [
            0 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']]
            ],
            1 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']]
            ]
        ];
        $a = [
            0 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']]
            ],
            1 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']]
            ]
        ];
        $a = Hash::sort($a, '{n}.Friend.{n}.name', 'desc');
        $this->assertEquals($a, $b);

        $a = [
            0 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']]
            ],
            1 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']]
            ],
            2 => [
                'Person' => ['name' => 'Adam'],
                'Friend' => [['name' => 'Bob']]
            ]
        ];
        $b = [
            0 => [
                'Person' => ['name' => 'Adam'],
                'Friend' => [['name' => 'Bob']]
            ],
            1 => [
                'Person' => ['name' => 'Jeff'],
                'Friend' => [['name' => 'Nate']]
            ],
            2 => [
                'Person' => ['name' => 'Tracy'],
                'Friend' => [['name' => 'Lindsay']]
            ]
        ];
        $a = Hash::sort($a, '{n}.Person.name', 'asc');
        $this->assertEquals($a, $b);

        $a = [
            0 => ['Person' => ['name' => 'Jeff']],
            1 => ['Shirt' => ['color' => 'black']]
        ];
        $b = [
            0 => ['Shirt' => ['color' => 'black']],
            1 => ['Person' => ['name' => 'Jeff']],
        ];
        $a = Hash::sort($a, '{n}.Person.name', 'ASC', 'STRING');
        $this->assertSame($a, $b);

        $names = [
            ['employees' => [
                ['name' => ['first' => 'John', 'last' => 'Doe']]]
            ],
            ['employees' => [
                ['name' => ['first' => 'Jane', 'last' => 'Doe']]]
            ],
            ['employees' => [['name' => []]]],
            ['employees' => [['name' => []]]]
        ];
        $result = Hash::sort($names, '{n}.employees.0.name', 'asc');
        $expected = [
            ['employees' => [
                ['name' => ['first' => 'John', 'last' => 'Doe']]]
            ],
            ['employees' => [
                ['name' => ['first' => 'Jane', 'last' => 'Doe']]]
            ],
            ['employees' => [['name' => []]]],
            ['employees' => [['name' => []]]]
        ];
        $this->assertSame($expected, $result);

        $a = [
            'SU' => [
                'total_fulfillable' => 2
            ],
            'AA' => [
                'total_fulfillable' => 1
            ],
            'LX' => [
                'total_fulfillable' => 0
            ],
            'BL' => [
                'total_fulfillable' => 3
            ],
        ];
        $expected = [
            'LX' => [
                'total_fulfillable' => 0
            ],
            'AA' => [
                'total_fulfillable' => 1
            ],
            'SU' => [
                'total_fulfillable' => 2
            ],
            'BL' => [
                'total_fulfillable' => 3
            ],
        ];
        $result = Hash::sort($a, '{s}.total_fulfillable', 'asc');
        $this->assertSame($expected, $result);
    }

    /**
     * Test sort() with numeric option.
     *
     * @return void
     */
    public function testSortNumeric()
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
        $this->assertEquals($expected, $result);

        $result = Hash::sort($items, '{n}.Item.price', 'desc', 'numeric');
        $expected = [
            ['Item' => ['price' => '275,622']],
            ['Item' => ['price' => '230,888']],
            ['Item' => ['price' => '155,000']],
            ['Item' => ['price' => '139,000']],
            ['Item' => ['price' => '66,000']],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test natural sorting.
     *
     * @return void
     */
    public function testSortNatural()
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
        $this->assertEquals($expected, $result);

        $result = Hash::sort($items, '{n}.Item.image', 'asc', 'natural');
        $expected = [
            ['Item' => ['image' => 'img1.jpg']],
            ['Item' => ['image' => 'img2.jpg']],
            ['Item' => ['image' => 'img10.jpg']],
            ['Item' => ['image' => 'img12.jpg']],
            ['Item' => ['image' => 'img99.jpg']],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test sort() with locale option.
     *
     * @return void
     */
    public function testSortLocale()
    {
        // get the current locale
        $oldLocale = setlocale(LC_COLLATE, '0');

        // the de_DE.utf8 locale must be installed on the system where the test is performed
        setlocale(LC_COLLATE, 'de_DE.utf8');

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
        $this->assertEquals($expected, $result);

        // change to the original locale
        setlocale(LC_COLLATE, $oldLocale);
    }

    /**
     * Test that sort() with 'natural' type will fallback to 'regular' as SORT_NATURAL is introduced in PHP 5.4
     *
     * @return void
     */
    public function testSortNaturalFallbackToRegular()
    {
        $a = [
            0 => ['Person' => ['name' => 'Jeff']],
            1 => ['Shirt' => ['color' => 'black']]
        ];
        $b = [
            0 => ['Shirt' => ['color' => 'black']],
            1 => ['Person' => ['name' => 'Jeff']],
        ];
        $sorted = Hash::sort($a, '{n}.Person.name', 'asc', 'natural');
        $this->assertEquals($sorted, $b);
    }

    /**
     * test sorting with out of order keys.
     *
     * @return void
     */
    public function testSortWithOutOfOrderKeys()
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
        $this->assertEquals($expected, $result);

        $result = Hash::sort($data, '{n}.test2', 'asc');
        $this->assertEquals($expected, $result);
    }

    /**
     * test sorting with string keys.
     *
     * @return void
     */
    public function testSortString()
    {
        $toSort = [
            'four' => ['number' => 4, 'some' => 'foursome'],
            'six' => ['number' => 6, 'some' => 'sixsome'],
            'five' => ['number' => 5, 'some' => 'fivesome'],
            'two' => ['number' => 2, 'some' => 'twosome'],
            'three' => ['number' => 3, 'some' => 'threesome']
        ];
        $sorted = Hash::sort($toSort, '{s}.number', 'asc');
        $expected = [
            'two' => ['number' => 2, 'some' => 'twosome'],
            'three' => ['number' => 3, 'some' => 'threesome'],
            'four' => ['number' => 4, 'some' => 'foursome'],
            'five' => ['number' => 5, 'some' => 'fivesome'],
            'six' => ['number' => 6, 'some' => 'sixsome']
        ];
        $this->assertEquals($expected, $sorted);

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
        $this->assertEquals($expected, $result);
    }


    /**
     * test sorting with string ignoring case.
     *
     * @return void
     */
    public function testSortStringIgnoreCase()
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
        $this->assertEquals($expected, $sorted);
    }

    /**
     * test regular sorting ignoring case.
     *
     * @return void
     */
    public function testSortRegularIgnoreCase()
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
        $this->assertEquals($expected, $sorted);
    }

    /**
     * Test sorting on a nested key that is sometimes undefined.
     *
     * @return void
     */
    public function testSortSparse()
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
            ]
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
     *
     * @return void
     */
    public function testInsertSimple()
    {
        $a = [
            'pages' => ['name' => 'page']
        ];
        $result = Hash::insert($a, 'files', ['name' => 'files']);
        $expected = [
            'pages' => ['name' => 'page'],
            'files' => ['name' => 'files']
        ];
        $this->assertEquals($expected, $result);

        $a = [
            'pages' => ['name' => 'page']
        ];
        $result = Hash::insert($a, 'pages.name', []);
        $expected = [
            'pages' => ['name' => []],
        ];
        $this->assertEquals($expected, $result);

        $a = [
            'foo' => ['bar' => 'baz']
        ];
        $result = Hash::insert($a, 'some.0123.path', ['foo' => ['bar' => 'baz']]);
        $expected = ['foo' => ['bar' => 'baz']];
        $this->assertEquals($expected, Hash::get($result, 'some.0123.path'));
    }

    /**
     * Test inserting with multiple values.
     *
     * @return void
     */
    public function testInsertMulti()
    {
        $data = static::articleData();

        $result = Hash::insert($data, '{n}.Article.insert', 'value');
        $this->assertEquals('value', $result[0]['Article']['insert']);
        $this->assertEquals('value', $result[1]['Article']['insert']);

        $result = Hash::insert($data, '{n}.Comment.{n}.insert', 'value');
        $this->assertEquals('value', $result[0]['Comment'][0]['insert']);
        $this->assertEquals('value', $result[0]['Comment'][1]['insert']);

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
        $this->assertEquals($expected, $result);

        $data[3]['testable'] = true;
        $result = Hash::insert($data, '{n}[testable].Item[id=/\b2|\b4/].test', 2);
        $expected = [
            0 => ['Item' => ['id' => 1, 'title' => 'first']],
            1 => ['Item' => ['id' => 2, 'title' => 'second']],
            2 => ['Item' => ['id' => 3, 'title' => 'third']],
            3 => ['Item' => ['id' => 4, 'title' => 'fourth', 'test' => 2], 'testable' => true],
            4 => ['Item' => ['id' => 5, 'title' => 'fifth']],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that insert() can insert data over a string value.
     *
     * @return void
     */
    public function testInsertOverwriteStringValue()
    {
        $data = [
            'Some' => [
                'string' => 'value'
            ]
        ];
        $result = Hash::insert($data, 'Some.string.value', ['values']);
        $expected = [
            'Some' => [
                'string' => [
                    'value' => ['values']
                ]
            ]
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test remove() method.
     *
     * @return void
     */
    public function testRemove()
    {
        $a = [
            'pages' => ['name' => 'page'],
            'files' => ['name' => 'files']
        ];

        $result = Hash::remove($a, 'files');
        $expected = [
            'pages' => ['name' => 'page']
        ];
        $this->assertEquals($expected, $result);

        $a = [
            'pages' => [
                0 => ['name' => 'main'],
                1 => [
                    'name' => 'about',
                    'vars' => ['title' => 'page title']
                ]
            ]
        ];

        $result = Hash::remove($a, 'pages.1.vars');
        $expected = [
            'pages' => [
                0 => ['name' => 'main'],
                1 => ['name' => 'about']
            ]
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::remove($a, 'pages.2.vars');
        $expected = $a;
        $this->assertEquals($expected, $result);

        $a = [
            0 => [
                'name' => 'pages'
            ],
            1 => [
                'name' => 'files'
            ]
        ];

        $result = Hash::remove($a, '{n}[name=files]');
        $expected = [
            0 => [
                'name' => 'pages'
            ]
        ];
        $this->assertEquals($expected, $result);

        $array = [
            0 => 'foo',
            1 => [
                0 => 'baz'
            ]
        ];
        $expected = $array;
        $result = Hash::remove($array, '{n}.part');
        $this->assertEquals($expected, $result);
        $result = Hash::remove($array, '{n}.{n}.part');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test removing multiple values.
     *
     * @return void
     */
    public function testRemoveMulti()
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
        $this->assertEquals($expected, $result);

        $data[3]['testable'] = true;
        $result = Hash::remove($data, '{n}[testable].Item[id=/\b2|\b4/].title');
        $expected = [
            0 => ['Item' => ['id' => 1, 'title' => 'first']],
            1 => ['Item' => ['id' => 2, 'title' => 'second']],
            2 => ['Item' => ['id' => 3, 'title' => 'third']],
            3 => ['Item' => ['id' => 4], 'testable' => true],
            4 => ['Item' => ['id' => 5, 'title' => 'fifth']],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testCheck method
     *
     * @return void
     */
    public function testCheck()
    {
        $set = [
            'My Index 1' => ['First' => 'The first item']
        ];
        $this->assertTrue(Hash::check($set, 'My Index 1.First'));
        $this->assertTrue(Hash::check($set, 'My Index 1'));

        $set = [
            'My Index 1' => [
                'First' => [
                    'Second' => [
                        'Third' => [
                            'Fourth' => 'Heavy. Nesting.'
                        ]
                    ]
                ]
            ]
        ];
        $this->assertTrue(Hash::check($set, 'My Index 1.First.Second'));
        $this->assertTrue(Hash::check($set, 'My Index 1.First.Second.Third'));
        $this->assertTrue(Hash::check($set, 'My Index 1.First.Second.Third.Fourth'));
        $this->assertFalse(Hash::check($set, 'My Index 1.First.Seconds.Third.Fourth'));
    }

    /**
     * testCombine method
     *
     * @return void
     */
    public function testCombine()
    {
        $result = Hash::combine([], '{n}.User.id', '{n}.User.Data');
        $this->assertTrue(empty($result));

        $a = static::userData();

        $result = Hash::combine($a, '{n}.User.id');
        $expected = [2 => null, 14 => null, 25 => null];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.non-existant');
        $expected = [2 => null, 14 => null, 25 => null];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data');
        $expected = [
            2 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
            14 => ['user' => 'phpnut', 'name' => 'Larry E. Masters'],
            25 => ['user' => 'gwoo', 'name' => 'The Gwoo']];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name');
        $expected = [
            2 => 'Mariano Iglesias',
            14 => 'Larry E. Masters',
            25 => 'The Gwoo'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test combine() giving errors on key/value length mismatches.
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testCombineErrorMissingValue()
    {
        $data = [
            ['User' => ['id' => 1, 'name' => 'mark']],
            ['User' => ['name' => 'jose']],
        ];
        Hash::combine($data, '{n}.User.id', '{n}.User.name');
    }

    /**
     * test combine() giving errors on key/value length mismatches.
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testCombineErrorMissingKey()
    {
        $data = [
            ['User' => ['id' => 1, 'name' => 'mark']],
            ['User' => ['id' => 2]],
        ];
        Hash::combine($data, '{n}.User.id', '{n}.User.name');
    }

    /**
     * test combine() with a group path.
     *
     * @return void
     */
    public function testCombineWithGroupPath()
    {
        $a = static::userData();

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
        $expected = [
            1 => [
                2 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
                25 => ['user' => 'gwoo', 'name' => 'The Gwoo']
            ],
            2 => [
                14 => ['user' => 'phpnut', 'name' => 'Larry E. Masters']
            ]
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
        $expected = [
            1 => [
                2 => 'Mariano Iglesias',
                25 => 'The Gwoo'
            ],
            2 => [
                14 => 'Larry E. Masters'
            ]
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
        $expected = [
            1 => [
                2 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
                25 => ['user' => 'gwoo', 'name' => 'The Gwoo']
            ],
            2 => [
                14 => ['user' => 'phpnut', 'name' => 'Larry E. Masters']
            ]
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
        $expected = [
            1 => [
                2 => 'Mariano Iglesias',
                25 => 'The Gwoo'
            ],
            2 => [
                14 => 'Larry E. Masters'
            ]
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test combine with formatting rules.
     *
     * @return void
     */
    public function testCombineWithFormatting()
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
                25 => 'gwoo: The Gwoo'
            ],
            2 => [
                14 => 'phpnut: Larry E. Masters'
            ]
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine(
            $a,
            [
                '%s: %s',
                '{n}.User.Data.user',
                '{n}.User.Data.name'
            ],
            '{n}.User.id'
        );
        $expected = [
            'mariano.iglesias: Mariano Iglesias' => 2,
            'phpnut: Larry E. Masters' => 14,
            'gwoo: The Gwoo' => 25
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine(
            $a,
            ['%1$s: %2$d', '{n}.User.Data.user', '{n}.User.id'],
            '{n}.User.Data.name'
        );
        $expected = [
            'mariano.iglesias: 2' => 'Mariano Iglesias',
            'phpnut: 14' => 'Larry E. Masters',
            'gwoo: 25' => 'The Gwoo'
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::combine(
            $a,
            ['%2$d: %1$s', '{n}.User.Data.user', '{n}.User.id'],
            '{n}.User.Data.name'
        );
        $expected = [
            '2: mariano.iglesias' => 'Mariano Iglesias',
            '14: phpnut' => 'Larry E. Masters',
            '25: gwoo' => 'The Gwoo'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testFormat method
     *
     * @return void
     */
    public function testFormat()
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
            'gwoo, 25'
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::format(
            $data,
            ['{n}.User.Data.user', '{n}.User.id'],
            '%2$s, %1$s'
        );
        $expected = [
            '2, mariano.iglesias',
            '14, phpnut',
            '25, gwoo'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testFormattingNullValues method
     *
     * @return void
     */
    public function testFormatNullValues()
    {
        $data = [
            ['Person' => [
                'first_name' => 'Nate', 'last_name' => 'Abele', 'city' => 'Boston', 'state' => 'MA', 'something' => '42'
            ]],
            ['Person' => [
                'first_name' => 'Larry', 'last_name' => 'Masters', 'city' => 'Boondock', 'state' => 'TN', 'something' => null
            ]],
            ['Person' => [
                'first_name' => 'Garrett', 'last_name' => 'Woodworth', 'city' => 'Venice Beach', 'state' => 'CA', 'something' => null
            ]]
        ];

        $result = Hash::format($data, ['{n}.Person.something'], '%s');
        $expected = ['42', '', ''];
        $this->assertEquals($expected, $result);

        $result = Hash::format($data, ['{n}.Person.city', '{n}.Person.something'], '%s, %s');
        $expected = ['Boston, 42', 'Boondock, ', 'Venice Beach, '];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test map()
     *
     * @return void
     */
    public function testMap()
    {
        $data = static::articleData();

        $result = Hash::map($data, '{n}.Article.id', [$this, 'mapCallback']);
        $expected = [2, 4, 6, 8, 10];
        $this->assertEquals($expected, $result);
    }

    /**
     * testApply
     *
     * @return void
     */
    public function testApply()
    {
        $data = static::articleData();

        $result = Hash::apply($data, '{n}.Article.id', 'array_sum');
        $this->assertEquals(15, $result);
    }

    /**
     * Test reduce()
     *
     * @return void
     */
    public function testReduce()
    {
        $data = static::articleData();

        $result = Hash::reduce($data, '{n}.Article.id', [$this, 'reduceCallback']);
        $this->assertEquals(15, $result);
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
     *
     * @return void
     */
    public function testNestModel()
    {
        $input = [
            [
                'ModelName' => [
                    'id' => 1,
                    'parent_id' => null
                ],
            ],
            [
                'ModelName' => [
                    'id' => 2,
                    'parent_id' => 1
                ],
            ],
            [
                'ModelName' => [
                    'id' => 3,
                    'parent_id' => 1
                ],
            ],
            [
                'ModelName' => [
                    'id' => 4,
                    'parent_id' => 1
                ],
            ],
            [
                'ModelName' => [
                    'id' => 5,
                    'parent_id' => 1
                ],
            ],
            [
                'ModelName' => [
                    'id' => 6,
                    'parent_id' => null
                ],
            ],
            [
                'ModelName' => [
                    'id' => 7,
                    'parent_id' => 6
                ],
            ],
            [
                'ModelName' => [
                    'id' => 8,
                    'parent_id' => 6
                ],
            ],
            [
                'ModelName' => [
                    'id' => 9,
                    'parent_id' => 6
                ],
            ],
            [
                'ModelName' => [
                    'id' => 10,
                    'parent_id' => 6
                ]
            ]
        ];
        $expected = [
            [
                'ModelName' => [
                    'id' => 1,
                    'parent_id' => null
                ],
                'children' => [
                    [
                        'ModelName' => [
                            'id' => 2,
                            'parent_id' => 1
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 3,
                            'parent_id' => 1
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 4,
                            'parent_id' => 1
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 5,
                            'parent_id' => 1
                        ],
                        'children' => []
                    ],

                ]
            ],
            [
                'ModelName' => [
                    'id' => 6,
                    'parent_id' => null
                ],
                'children' => [
                    [
                        'ModelName' => [
                            'id' => 7,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 8,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 9,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 10,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ]
                ]
            ]
        ];
        $result = Hash::nest($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * test Hash nest with a normal model result set, and a nominated root id
     *
     * @return void
     */
    public function testNestModelExplicitRoot()
    {
        $input = [
            [
                'ModelName' => [
                    'id' => 1,
                    'parent_id' => null
                ],
            ],
            [
                'ModelName' => [
                    'id' => 2,
                    'parent_id' => 1
                ],
            ],
            [
                'ModelName' => [
                    'id' => 3,
                    'parent_id' => 1
                ],
            ],
            [
                'ModelName' => [
                    'id' => 4,
                    'parent_id' => 1
                ],
            ],
            [
                'ModelName' => [
                    'id' => 5,
                    'parent_id' => 1
                ],
            ],
            [
                'ModelName' => [
                    'id' => 6,
                    'parent_id' => null
                ],
            ],
            [
                'ModelName' => [
                    'id' => 7,
                    'parent_id' => 6
                ],
            ],
            [
                'ModelName' => [
                    'id' => 8,
                    'parent_id' => 6
                ],
            ],
            [
                'ModelName' => [
                    'id' => 9,
                    'parent_id' => 6
                ],
            ],
            [
                'ModelName' => [
                    'id' => 10,
                    'parent_id' => 6
                ]
            ]
        ];
        $expected = [
            [
                'ModelName' => [
                    'id' => 6,
                    'parent_id' => null
                ],
                'children' => [
                    [
                        'ModelName' => [
                            'id' => 7,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 8,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 9,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    [
                        'ModelName' => [
                            'id' => 10,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ]
                ]
            ]
        ];
        $result = Hash::nest($input, ['root' => 6]);
        $this->assertEquals($expected, $result);
    }

    /**
     * test Hash nest with a 1d array - this method should be able to handle any type of array input
     *
     * @return void
     */
    public function testNest1Dimensional()
    {
        $input = [
            [
                'id' => 1,
                'parent_id' => null
            ],
            [
                'id' => 2,
                'parent_id' => 1
            ],
            [
                'id' => 3,
                'parent_id' => 1
            ],
            [
                'id' => 4,
                'parent_id' => 1
            ],
            [
                'id' => 5,
                'parent_id' => 1
            ],
            [
                'id' => 6,
                'parent_id' => null
            ],
            [
                'id' => 7,
                'parent_id' => 6
            ],
            [
                'id' => 8,
                'parent_id' => 6
            ],
            [
                'id' => 9,
                'parent_id' => 6
            ],
            [
                'id' => 10,
                'parent_id' => 6
            ]
        ];
        $expected = [
            [
                'id' => 1,
                'parent_id' => null,
                'children' => [
                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'children' => []
                    ],
                    [
                        'id' => 3,
                        'parent_id' => 1,
                        'children' => []
                    ],
                    [
                        'id' => 4,
                        'parent_id' => 1,
                        'children' => []
                    ],
                    [
                        'id' => 5,
                        'parent_id' => 1,
                        'children' => []
                    ],

                ]
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'children' => [
                    [
                        'id' => 7,
                        'parent_id' => 6,
                        'children' => []
                    ],
                    [
                        'id' => 8,
                        'parent_id' => 6,
                        'children' => []
                    ],
                    [
                        'id' => 9,
                        'parent_id' => 6,
                        'children' => []
                    ],
                    [
                        'id' => 10,
                        'parent_id' => 6,
                        'children' => []
                    ]
                ]
            ]
        ];
        $result = Hash::nest($input, ['idPath' => '{n}.id', 'parentPath' => '{n}.parent_id']);
        $this->assertEquals($expected, $result);
    }

    /**
     * test Hash nest with no specified parent data.
     *
     * The result should be the same as the input.
     * For an easier comparison, unset all the empty children arrays from the result
     *
     * @return void
     */
    public function testMissingParent()
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
            ]
        ];

        $result = Hash::nest($input, ['idPath' => '{n}.id', 'parentPath' => '{n}.parent_id']);
        foreach ($result as &$row) {
            if (empty($row['children'])) {
                unset($row['children']);
            }
        }
        $this->assertEquals($input, $result);
    }

    /**
     * Tests that nest() throws an InvalidArgumentException when providing an invalid input.
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testNestInvalid()
    {
        $input = [
            [
                'ParentCategory' => [
                    'id' => '1',
                    'name' => 'Lorem ipsum dolor sit amet',
                    'parent_id' => '1'
                ]
            ]
        ];
        Hash::nest($input);
    }

    /**
     * testMergeDiff method
     *
     * @return void
     */
    public function testMergeDiff()
    {
        $first = [
            'ModelOne' => [
                'id' => 1001,
                'field_one' => 'a1.m1.f1',
                'field_two' => 'a1.m1.f2'
            ]
        ];
        $second = [
            'ModelTwo' => [
                'id' => 1002,
                'field_one' => 'a2.m2.f1',
                'field_two' => 'a2.m2.f2'
            ]
        ];
        $result = Hash::mergeDiff($first, $second);
        $this->assertEquals($result, $first + $second);

        $result = Hash::mergeDiff($first, []);
        $this->assertEquals($result, $first);

        $result = Hash::mergeDiff([], $first);
        $this->assertEquals($result, $first);

        $third = [
            'ModelOne' => [
                'id' => 1003,
                'field_one' => 'a3.m1.f1',
                'field_two' => 'a3.m1.f2',
                'field_three' => 'a3.m1.f3'
            ]
        ];
        $result = Hash::mergeDiff($first, $third);
        $expected = [
            'ModelOne' => [
                'id' => 1001,
                'field_one' => 'a1.m1.f1',
                'field_two' => 'a1.m1.f2',
                'field_three' => 'a3.m1.f3'
            ]
        ];
        $this->assertEquals($expected, $result);

        $first = [
            0 => ['ModelOne' => ['id' => 1001, 'field_one' => 's1.0.m1.f1', 'field_two' => 's1.0.m1.f2']],
            1 => ['ModelTwo' => ['id' => 1002, 'field_one' => 's1.1.m2.f2', 'field_two' => 's1.1.m2.f2']]
        ];
        $second = [
            0 => ['ModelOne' => ['id' => 1001, 'field_one' => 's2.0.m1.f1', 'field_two' => 's2.0.m1.f2']],
            1 => ['ModelTwo' => ['id' => 1002, 'field_one' => 's2.1.m2.f2', 'field_two' => 's2.1.m2.f2']]
        ];

        $result = Hash::mergeDiff($first, $second);
        $this->assertEquals($result, $first);

        $third = [
            0 => [
                'ModelThree' => [
                    'id' => 1003,
                    'field_one' => 's3.0.m3.f1',
                    'field_two' => 's3.0.m3.f2'
                ]
            ]
        ];

        $result = Hash::mergeDiff($first, $third);
        $expected = [
            0 => [
                'ModelOne' => [
                    'id' => 1001,
                    'field_one' => 's1.0.m1.f1',
                    'field_two' => 's1.0.m1.f2'
                ],
                'ModelThree' => [
                    'id' => 1003,
                    'field_one' => 's3.0.m3.f1',
                    'field_two' => 's3.0.m3.f2'
                ]
            ],
            1 => [
                'ModelTwo' => [
                    'id' => 1002,
                    'field_one' => 's1.1.m2.f2',
                    'field_two' => 's1.1.m2.f2'
                ]
            ]
        ];
        $this->assertEquals($expected, $result);

        $result = Hash::mergeDiff($first, []);
        $this->assertEquals($result, $first);

        $result = Hash::mergeDiff($first, $second);
        $this->assertEquals($result, $first + $second);
    }

    /**
     * Tests Hash::expand
     *
     * @return void
     */
    public function testExpand()
    {
        $data = ['My', 'Array', 'To', 'Flatten'];
        $flat = Hash::flatten($data);
        $result = Hash::expand($flat);
        $this->assertEquals($data, $result);

        $data = [
            '0.Post.id' => '1', '0.Post.author_id' => '1', '0.Post.title' => 'First Post', '0.Author.id' => '1',
            '0.Author.user' => 'nate', '0.Author.password' => 'foo', '1.Post.id' => '2', '1.Post.author_id' => '3',
            '1.Post.title' => 'Second Post', '1.Post.body' => 'Second Post Body', '1.Author.id' => '3',
            '1.Author.user' => 'larry', '1.Author.password' => null
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
            ]
        ];
        $this->assertEquals($expected, $result);

        $data = [
            '0/Post/id' => 1,
            '0/Post/name' => 'test post'
        ];
        $result = Hash::expand($data, '/');
        $expected = [
            [
                'Post' => [
                    'id' => 1,
                    'name' => 'test post'
                ]
            ]
        ];
        $this->assertEquals($expected, $result);

        $data = ['a.b.100.a' => null, 'a.b.200.a' => null];
        $expected = [
            'a' => [
                'b' => [
                    100 => ['a' => null],
                    200 => ['a' => null]
                ]
            ]
        ];
        $result = Hash::expand($data);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that flattening a large complex set doesn't loop forever.
     *
     * @return void
     */
    public function testFlattenInfiniteLoop()
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
            'Order.type' => 'ttt'
        ];
        $expanded = Hash::expand($data);
        $flattened = Hash::flatten($expanded);
        $this->assertEquals($data, $flattened);
    }
}
