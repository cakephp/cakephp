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
namespace Cake\Test\TestCase\ORM\Behavior;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Translate behavior test case
 */
class TreeBehaviorTest extends TestCase
{
    /**
     * fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'core.MenuLinkTrees',
        'core.NumberTrees',
        'core.NumberTreesArticles',
    ];

    /**
     * @var \Cake\ORM\Table|\Cake\ORM\Behavior\TreeBehavior
     */
    protected $table;

    public function setUp(): void
    {
        parent::setUp();
        $this->table = $this->getTableLocator()->get('NumberTrees');
        $this->table->setPrimaryKey(['id']);
        $this->table->addBehavior('Tree');
    }

    /**
     * Sanity test
     *
     * Make sure the assert method acts as you'd expect, this is the expected
     * initial db state
     */
    public function testAssertMpttValues(): void
    {
        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:19 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '__17:18 - 10:radios',
            '21:22 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);

        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);

        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);

        $table->removeBehavior('Tree');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'categories']]);
        $expected = [
            ' 1:10 -  9:electronics',
            '_ 2: 9 - 10:televisions',
            '__ 3: 4 - 11:tube',
            '__ 5: 8 - 12:lcd',
            '___ 6: 7 - 13:plasma',
            '11:20 - 14:portable',
            '_12:15 - 15:mp3',
            '__13:14 - 16:flash',
            '_16:17 - 17:cd',
            '_18:19 - 18:radios',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests the find('path') method
     */
    public function testFindPath(): void
    {
        $nodes = $this->table->find('path', ['for' => 9]);
        $this->assertEquals([1, 6, 9], $nodes->all()->extract('id')->toArray());

        $nodes = $this->table->find('path', ['for' => 10]);
        $this->assertSame([1, 6, 10], $nodes->all()->extract('id')->toArray());

        $nodes = $this->table->find('path', ['for' => 5]);
        $this->assertSame([1, 2, 5], $nodes->all()->extract('id')->toArray());

        $nodes = $this->table->find('path', ['for' => 1]);
        $this->assertSame([1], $nodes->all()->extract('id')->toArray());

        $entity = $this->table->newEntity(['name' => 'odd one', 'parent_id' => 1]);
        $entity = $this->table->save($entity);
        $newId = $entity->id;

        $entity = $this->table->get(2);
        $entity->parent_id = $newId;
        $this->table->save($entity);

        $nodes = $this->table->find('path', ['for' => 4]);
        $this->assertSame([1, $newId, 2, 4], $nodes->all()->extract('id')->toArray());

        // find path with scope
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $nodes = $table->find('path', ['for' => 5]);
        $this->assertSame([1, 3, 4, 5], $nodes->all()->extract('id')->toArray());
    }

    /**
     * Tests the childCount() method
     */
    public function testChildCount(): void
    {
        // direct children for the root node
        $table = $this->table;
        $countDirect = $this->table->childCount($table->get(1), true);
        $this->assertSame(2, $countDirect);

        // counts all the children of root
        $count = $this->table->childCount($table->get(1), false);
        $this->assertSame(9, $count);

        // counts direct children
        $count = $this->table->childCount($table->get(2), false);
        $this->assertSame(3, $count);

        // count children for a middle-node
        $count = $this->table->childCount($table->get(6), false);
        $this->assertSame(4, $count);

        // count leaf children
        $count = $this->table->childCount($table->get(10), false);
        $this->assertSame(0, $count);

        // test scoping
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $count = $table->childCount($table->get(3), false);
        $this->assertSame(2, $count);
    }

    /**
     * Tests that childCount will provide the correct lft and rght values
     */
    public function testChildCountNoTreeColumns(): void
    {
        $table = $this->table;
        $node = $table->get(6);
        $node->unset('lft');
        $node->unset('rght');
        $count = $this->table->childCount($node, false);
        $this->assertSame(4, $count);
    }

    /**
     * Tests the childCount() plus callable scoping
     */
    public function testScopeCallable(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', [
            'scope' => function ($query) {
                return $query->where(['menu' => 'main-menu']);
            },
        ]);
        $count = $table->childCount($table->get(1), false);
        $this->assertSame(4, $count);
    }

    /**
     * Tests the find('children') method
     */
    public function testFindChildren(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);

        // root
        $nodeIds = [];
        $nodes = $table->find('children', ['for' => 1])->all();
        $this->assertEquals([2, 3, 4, 5], $nodes->extract('id')->toArray());

        // leaf
        $nodeIds = [];
        $nodes = $table->find('children', ['for' => 5])->all();
        $this->assertCount(0, $nodes->extract('id')->toArray());

        // direct children
        $nodes = $table->find('children', ['for' => 1, 'direct' => true])->all();
        $this->assertEquals([2, 3], $nodes->extract('id')->toArray());
    }

    /**
     * Tests the find('children') plus scope=null
     */
    public function testScopeNull(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree');
        $table->behaviors()->get('Tree')->setConfig('scope', null);

        $nodes = $table->find('children', ['for' => 1, 'direct' => true])->all();
        $this->assertEquals([2, 3], $nodes->extract('id')->toArray());
    }

    /**
     * Tests that find('children') will throw an exception if the node was not found
     */
    public function testFindChildrenException(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $query = $table->find('children', ['for' => 500]);
    }

    /**
     * Tests the find('treeList') method
     */
    public function testFindTreeList(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $query = $table->find('treeList');

        $result = null;
        $query->clause('order')->iterateParts(function ($dir, $field) use (&$result): void {
            $result = $field;
        });
        $this->assertSame('MenuLinkTrees.lft', $result);

        $result = $query->toArray();
        $expected = [
            1 => 'Link 1',
            2 => '_Link 2',
            3 => '_Link 3',
            4 => '__Link 4',
            5 => '___Link 5',
            6 => 'Link 6',
            7 => '_Link 7',
            8 => 'Link 8',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the find('treeList') method after moveUp, moveDown
     */
    public function testFindTreeListAfterMove(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);

        // moveUp
        $table->moveUp($table->get(3), 1);
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 7 -  3:Link 3',
            '__ 3: 6 -  4:Link 4',
            '___ 4: 5 -  5:Link 5',
            '_ 8: 9 -  2:Link 2',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);

        // moveDown
        $table->moveDown($table->get(6), 1);
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 7 -  3:Link 3',
            '__ 3: 6 -  4:Link 4',
            '___ 4: 5 -  5:Link 5',
            '_ 8: 9 -  2:Link 2',
            '11:12 -  8:Link 8',
            '13:16 -  6:Link 6',
            '_14:15 -  7:Link 7',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests the find('treeList') method with custom options
     */
    public function testFindTreeListCustom(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $result = $table
            ->find('treeList', ['keyPath' => 'url', 'valuePath' => 'id', 'spacer' => ' '])
            ->toArray();
        $expected = [
            '/link1.html' => '1',
            'http://example.com' => ' 2',
            '/what/even-more-links.html' => ' 3',
            '/lorem/ipsum.html' => '  4',
            '/what/the.html' => '   5',
            '/yeah/another-link.html' => '6',
            'https://cakephp.org' => ' 7',
            '/page/who-we-are.html' => '8',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the testFormatTreeListCustom() method.
     */
    public function testFormatTreeListCustom(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree');

        $query = $table
            ->find('threaded')
            ->where(['menu' => 'main-menu']);

        $options = ['keyPath' => 'url', 'valuePath' => 'id', 'spacer' => ' '];
        $result = $table->formatTreeList($query, $options)->toArray();

        $expected = [
            '/link1.html' => '1',
            'http://example.com' => ' 2',
            '/what/even-more-links.html' => ' 3',
            '/lorem/ipsum.html' => '  4',
            '/what/the.html' => '   5',
            '/yeah/another-link.html' => '6',
            'https://cakephp.org' => ' 7',
            '/page/who-we-are.html' => '8',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the moveUp() method
     */
    public function testMoveUp(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);

        // top level, won't move
        $node = $this->table->moveUp($table->get(1), 10);
        $this->assertEquals(['lft' => 1, 'rght' => 10], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);

        // edge cases
        $this->assertFalse($this->table->moveUp($table->get(1), 0));
        $this->assertFalse($this->table->moveUp($table->get(1), -10));
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);

        // move inner node
        $node = $table->moveUp($table->get(3), 1);
        $nodes = $table->find('children', ['for' => 1])->all();
        $this->assertEquals(['lft' => 2, 'rght' => 7], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 7 -  3:Link 3',
            '__ 3: 6 -  4:Link 4',
            '___ 4: 5 -  5:Link 5',
            '_ 8: 9 -  2:Link 2',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node with no siblings
     */
    public function testMoveLeaf(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $node = $table->moveUp($table->get(5), 1);
        $this->assertEquals(['lft' => 6, 'rght' => 7], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node to the top
     */
    public function testMoveTop(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $node = $table->moveUp($table->get(8), true);
        $expected = [
            ' 1: 2 -  8:Link 8',
            ' 3:12 -  1:Link 1',
            '_ 4: 5 -  2:Link 2',
            '_ 6:11 -  3:Link 3',
            '__ 7:10 -  4:Link 4',
            '___ 8: 9 -  5:Link 5',
            '13:16 -  6:Link 6',
            '_14:15 -  7:Link 7',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node with no lft and rght
     */
    public function testMoveNoTreeColumns(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $node = $table->get(8);
        $node->unset('lft');
        $node->unset('rght');
        $node = $table->moveUp($node, true);
        $this->assertEquals(['lft' => 1, 'rght' => 2], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1: 2 -  8:Link 8',
            ' 3:12 -  1:Link 1',
            '_ 4: 5 -  2:Link 2',
            '_ 6:11 -  3:Link 3',
            '__ 7:10 -  4:Link 4',
            '___ 8: 9 -  5:Link 5',
            '13:16 -  6:Link 6',
            '_14:15 -  7:Link 7',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests the moveDown() method
     */
    public function testMoveDown(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        // latest node, won't move
        $node = $table->moveDown($table->get(8), 10);
        $this->assertEquals(['lft' => 15, 'rght' => 16], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);

        // edge cases
        $this->assertFalse($this->table->moveDown($table->get(8), 0));
        $this->assertFalse($this->table->moveDown($table->get(8), -10));
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);

        // move inner node
        $node = $table->moveDown($table->get(2), 1);
        $this->assertEquals(['lft' => 8, 'rght' => 9], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 7 -  3:Link 3',
            '__ 3: 6 -  4:Link 4',
            '___ 4: 5 -  5:Link 5',
            '_ 8: 9 -  2:Link 2',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node that has no siblings
     */
    public function testMoveLeafDown(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $node = $table->moveDown($table->get(5), 1);
        $this->assertEquals(['lft' => 6, 'rght' => 7], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node to the bottom
     */
    public function testMoveToBottom(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $node = $table->moveDown($table->get(1), true);
        $this->assertEquals(['lft' => 7, 'rght' => 16], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1: 4 -  6:Link 6',
            '_ 2: 3 -  7:Link 7',
            ' 5: 6 -  8:Link 8',
            ' 7:16 -  1:Link 1',
            '_ 8: 9 -  2:Link 2',
            '_10:15 -  3:Link 3',
            '__11:14 -  4:Link 4',
            '___12:13 -  5:Link 5',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node with no lft and rght columns
     */
    public function testMoveDownNoTreeColumns(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $node = $table->get(1);
        $node->unset('lft');
        $node->unset('rght');
        $node = $table->moveDown($node, true);
        $this->assertEquals(['lft' => 7, 'rght' => 16], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1: 4 -  6:Link 6',
            '_ 2: 3 -  7:Link 7',
            ' 5: 6 -  8:Link 8',
            ' 7:16 -  1:Link 1',
            '_ 8: 9 -  2:Link 2',
            '_10:15 -  3:Link 3',
            '__11:14 -  4:Link 4',
            '___12:13 -  5:Link 5',
        ];
        $this->assertMpttValues($expected, $table);
    }

    public function testMoveDownMultiplePositions(): void
    {
        $node = $this->table->moveDown($this->table->get(3), 2);
        $this->assertEquals(['lft' => 7, 'rght' => 8], $node->extract(['lft', 'rght']));
        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  4:lcd',
            '__ 5: 6 -  5:plasma',
            '__ 7: 8 -  3:tube',
            '_10:19 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '__17:18 - 10:radios',
            '21:22 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests the recover function
     */
    public function testRecover(): void
    {
        $table = $this->table;

        $expectedLevels = $table
            ->find('list', ['valueField' => 'depth'])
            ->order('lft')
            ->toArray();
        $table->updateAll(['lft' => null, 'rght' => null, 'depth' => null], []);
        $table->behaviors()->Tree->setConfig('level', 'depth');
        $table->recover();

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:19 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '__17:18 - 10:radios',
            '21:22 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $table);

        $result = $table
            ->find('list', ['valueField' => 'depth'])
            ->order('lft')
            ->toArray();
        $this->assertSame($expectedLevels, $result);
    }

    /**
     * Tests the recover function with a custom scope
     */
    public function testRecoverScoped(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $table->updateAll(['lft' => null, 'rght' => null], ['menu' => 'main-menu']);
        $table->recover();

        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);

        $table->removeBehavior('Tree');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'categories']]);
        $expected = [
            ' 1:10 -  9:electronics',
            '_ 2: 9 - 10:televisions',
            '__ 3: 4 - 11:tube',
            '__ 5: 8 - 12:lcd',
            '___ 6: 7 - 13:plasma',
            '11:20 - 14:portable',
            '_12:15 - 15:mp3',
            '__13:14 - 16:flash',
            '_16:17 - 17:cd',
            '_18:19 - 18:radios',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Test recover function with a custom order clause
     */
    public function testRecoverWithCustomOrder(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu'], 'recoverOrder' => ['MenuLinkTrees.title' => 'desc']]);
        $table->updateAll(['lft' => null, 'rght' => null], ['menu' => 'main-menu']);
        $table->recover();

        $expected = [
            ' 1: 2 -  8:Link 8',
            ' 3: 6 -  6:Link 6',
            '_ 4: 5 -  7:Link 7',
            ' 7:16 -  1:Link 1',
            '_ 8:13 -  3:Link 3',
            '__ 9:12 -  4:Link 4',
            '___10:11 -  5:Link 5',
            '_14:15 -  2:Link 2',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests adding a new orphan node
     */
    public function testAddOrphan(): void
    {
        $table = $this->table;
        $entity = new Entity(
            ['name' => 'New Orphan', 'parent_id' => null, 'level' => null],
            ['markNew' => true]
        );
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(23, $entity->lft);
        $this->assertSame(24, $entity->rght);

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:19 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '__17:18 - 10:radios',
            '21:22 - 11:alien hardware',
            '23:24 - 12:New Orphan',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests that adding a child node as a descendant of one of the roots works
     */
    public function testAddMiddle(): void
    {
        $table = $this->table;
        $entity = new Entity(
            ['name' => 'laptops', 'parent_id' => 1],
            ['markNew' => true]
        );
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(20, $entity->lft);
        $this->assertSame(21, $entity->rght);

        $expected = [
            ' 1:22 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:19 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '__17:18 - 10:radios',
            '_20:21 - 12:laptops',
            '23:24 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests adding a leaf to the tree
     */
    public function testAddLeaf(): void
    {
        $table = $this->table;
        $entity = new Entity(
            ['name' => 'laptops', 'parent_id' => 2],
            ['markNew' => true]
        );
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(9, $entity->lft);
        $this->assertSame(10, $entity->rght);

        $expected = [
            ' 1:22 -  1:electronics',
            '_ 2:11 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '__ 9:10 - 12:laptops',
            '_12:21 -  6:portable',
            '__13:16 -  7:mp3',
            '___14:15 -  8:flash',
            '__17:18 -  9:cd',
            '__19:20 - 10:radios',
            '23:24 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests adding a root element to the tree when all other root elements have children
     */
    public function testAddRoot(): void
    {
        $table = $this->table;

        //First add a child to the empty root element
        $alien = $table->find()->where(['name' => 'alien hardware'])->first();
        $entity = new Entity(['name' => 'plasma rifle', 'parent_id' => $alien->id], ['markNew' => true]);
        $table->save($entity);

        $entity = new Entity(['name' => 'carpentry', 'parent_id' => null], ['markNew' => true]);
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(25, $entity->lft);
        $this->assertSame(26, $entity->rght);

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:19 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '__17:18 - 10:radios',
            '21:24 - 11:alien hardware',
            '_22:23 - 12:plasma rifle',
            '25:26 - 13:carpentry',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests making a node its own parent as an existing entity
     */
    public function testReParentSelf(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot set a node\'s parent as itself');
        $entity = $this->table->get(1);
        $entity->parent_id = $entity->id;
        $this->table->save($entity);
    }

    /**
     * Tests making a node its own parent as a new entity.
     */
    public function testReParentSelfNewEntity(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot set a node\'s parent as itself');
        $entity = $this->table->newEntity(['name' => 'root']);
        $entity->id = 1;
        $entity->parent_id = $entity->id;
        $this->table->save($entity);
    }

    /**
     * Tests moving a subtree to the right
     */
    public function testReParentSubTreeRight(): void
    {
        $table = $this->table;
        $entity = $table->get(2);
        $entity->parent_id = 6;
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(11, $entity->lft);
        $this->assertSame(18, $entity->rght);

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2:19 -  6:portable',
            '__ 3: 6 -  7:mp3',
            '___ 4: 5 -  8:flash',
            '__ 7: 8 -  9:cd',
            '__ 9:10 - 10:radios',
            '__11:18 -  2:televisions',
            '___12:13 -  3:tube',
            '___14:15 -  4:lcd',
            '___16:17 -  5:plasma',
            '21:22 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a subtree to the left
     */
    public function testReParentSubTreeLeft(): void
    {
        $table = $this->table;
        $entity = $table->get(6);
        $entity->parent_id = 2;
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(9, $entity->lft);
        $this->assertSame(18, $entity->rght);

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2:19 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '__ 9:18 -  6:portable',
            '___10:13 -  7:mp3',
            '____11:12 -  8:flash',
            '___14:15 -  9:cd',
            '___16:17 - 10:radios',
            '21:22 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Test moving a leaft to the left
     */
    public function testReParentLeafLeft(): void
    {
        $table = $this->table;
        $entity = $table->get(10);
        $entity->parent_id = 2;
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(9, $entity->lft);
        $this->assertSame(10, $entity->rght);

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2:11 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '__ 9:10 - 10:radios',
            '_12:19 -  6:portable',
            '__13:16 -  7:mp3',
            '___14:15 -  8:flash',
            '__17:18 -  9:cd',
            '21:22 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Test moving a leaf to the left
     */
    public function testReParentLeafRight(): void
    {
        $table = $this->table;
        $entity = $table->get(5);
        $entity->parent_id = 6;
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(17, $entity->lft);
        $this->assertSame(18, $entity->rght);

        $result = $table->find()->order('lft')->enableHydration(false);

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2: 7 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '_ 8:19 -  6:portable',
            '__ 9:12 -  7:mp3',
            '___10:11 -  8:flash',
            '__13:14 -  9:cd',
            '__15:16 - 10:radios',
            '__17:18 -  5:plasma',
            '21:22 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a subtree with a node having no lft and rght columns
     */
    public function testReParentNoTreeColumns(): void
    {
        $table = $this->table;
        $entity = $table->get(6);
        $entity->unset('lft');
        $entity->unset('rght');
        $entity->parent_id = 2;
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(9, $entity->lft);
        $this->assertSame(18, $entity->rght);

        $expected = [
            ' 1:20 -  1:electronics',
            '_ 2:19 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '__ 9:18 -  6:portable',
            '___10:13 -  7:mp3',
            '____11:12 -  8:flash',
            '___14:15 -  9:cd',
            '___16:17 - 10:radios',
            '21:22 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests moving a subtree as a new root
     */
    public function testRootingSubTree(): void
    {
        $table = $this->table;
        $entity = $table->get(2);
        $entity->parent_id = null;
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(15, $entity->lft);
        $this->assertSame(22, $entity->rght);

        $expected = [
            ' 1:12 -  1:electronics',
            '_ 2:11 -  6:portable',
            '__ 3: 6 -  7:mp3',
            '___ 4: 5 -  8:flash',
            '__ 7: 8 -  9:cd',
            '__ 9:10 - 10:radios',
            '13:14 - 11:alien hardware',
            '15:22 -  2:televisions',
            '_16:17 -  3:tube',
            '_18:19 -  4:lcd',
            '_20:21 -  5:plasma',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a subtree with no tree columns
     */
    public function testRootingNoTreeColumns(): void
    {
        $table = $this->table;
        $entity = $table->get(2);
        $entity->unset('lft');
        $entity->unset('rght');
        $entity->parent_id = null;
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(15, $entity->lft);
        $this->assertSame(22, $entity->rght);

        $expected = [
            ' 1:12 -  1:electronics',
            '_ 2:11 -  6:portable',
            '__ 3: 6 -  7:mp3',
            '___ 4: 5 -  8:flash',
            '__ 7: 8 -  9:cd',
            '__ 9:10 - 10:radios',
            '13:14 - 11:alien hardware',
            '15:22 -  2:televisions',
            '_16:17 -  3:tube',
            '_18:19 -  4:lcd',
            '_20:21 -  5:plasma',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests that trying to create a cycle throws an exception
     */
    public function testReparentCycle(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot use node "5" as parent for entity "2"');
        $table = $this->table;
        $entity = $table->get(2);
        $entity->parent_id = 5;
        $table->save($entity);
    }

    /**
     * Tests deleting a leaf in the tree
     */
    public function testDeleteLeaf(): void
    {
        $table = $this->table;
        $entity = $table->get(4);
        $this->assertTrue($table->delete($entity));

        $expected = [
            ' 1:18 -  1:electronics',
            '_ 2: 7 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  5:plasma',
            '_ 8:17 -  6:portable',
            '__ 9:12 -  7:mp3',
            '___10:11 -  8:flash',
            '__13:14 -  9:cd',
            '__15:16 - 10:radios',
            '19:20 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests deleting a subtree
     */
    public function testDeleteSubTree(): void
    {
        $table = $this->table;
        $entity = $table->get(6);
        $this->assertTrue($table->delete($entity));

        $expected = [
            ' 1:10 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '11:12 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests deleting a subtree in a scoped tree
     */
    public function testDeleteSubTreeScopedTree(): void
    {
        $table = $this->getTableLocator()->get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $entity = $table->get(3);
        $this->assertTrue($table->delete($entity));

        $expected = [
            ' 1: 4 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            ' 5: 8 -  6:Link 6',
            '_ 6: 7 -  7:Link 7',
            ' 9:10 -  8:Link 8',
        ];
        $this->assertMpttValues($expected, $table);

        $table->behaviors()->get('Tree')->setConfig('scope', ['menu' => 'categories']);
        $expected = [
            ' 1:10 -  9:electronics',
            '_ 2: 9 - 10:televisions',
            '__ 3: 4 - 11:tube',
            '__ 5: 8 - 12:lcd',
            '___ 6: 7 - 13:plasma',
            '11:20 - 14:portable',
            '_12:15 - 15:mp3',
            '__13:14 - 16:flash',
            '_16:17 - 17:cd',
            '_18:19 - 18:radios',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests deleting a subtree with ORM delete callbacks
     */
    public function testDeleteSubTreeWithCallbacks(): void
    {
        $NumberTreesArticles = $this->getTableLocator()->get('NumberTreesArticles');
        $newArticle = $NumberTreesArticles->newEntity([
            'number_tree_id' => 7, // Link to sub-tree item
            'title' => 'New Article',
            'body' => 'New Article Body',
            'published' => 'Y',
        ]);
        $NumberTreesArticles->save($newArticle);

        $table = $this->table;
        $table->addAssociations([
            'hasMany' => [
                'NumberTreesArticles' => [
                    'cascadeCallbacks' => true,
                    'dependent' => true,
                ],
            ],
        ]);
        $table->getBehavior('Tree')->setConfig(['cascadeCallbacks' => true]);

        // Delete parent category
        $entity = $table->get(6);
        $this->assertTrue($table->delete($entity));

        $expected = [
            ' 1:12 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '13:14 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);

        // Check if new article which was linked to sub-category was deleted
        $count = $NumberTreesArticles->find()
            ->where(['number_tree_id' => 7])
            ->count();
        $this->assertSame(0, $count);
    }

    /**
     * Test deleting a root node
     */
    public function testDeleteRoot(): void
    {
        $table = $this->table;
        $entity = $table->get(1);
        $this->assertTrue($table->delete($entity));

        $expected = [
            ' 1: 2 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Test deleting a node with no tree columns
     */
    public function testDeleteRootNoTreeColumns(): void
    {
        $table = $this->table;
        $entity = $table->get(1);
        $entity->unset('lft');
        $entity->unset('rght');
        $this->assertTrue($table->delete($entity));

        $expected = [
            ' 1: 2 - 11:alien hardware',
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests that a leaf can be taken out of the tree and put in as a root
     */
    public function testRemoveFromLeafFromTree(): void
    {
        $table = $this->table;
        $entity = $table->get(10);
        $this->assertSame($entity, $table->removeFromTree($entity));
        $this->assertSame(21, $entity->lft);
        $this->assertSame(22, $entity->rght);
        $this->assertNull($entity->parent_id);
        $result = $table->find()->order('lft')->enableHydration(false);
        $expected = [
            ' 1:18 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:17 -  6:portable',
            '__11:14 -  7:mp3',
            '___12:13 -  8:flash',
            '__15:16 -  9:cd',
            '19:20 - 11:alien hardware',
            '21:22 - 10:radios',

        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Test removing a middle node from a tree
     */
    public function testRemoveMiddleNodeFromTree(): void
    {
        $table = $this->table;
        $entity = $table->get(6);
        $this->assertSame($entity, $table->removeFromTree($entity));
        $result = $table->find('threaded')->order('lft')->enableHydration(false)->toArray();
        $this->assertSame(21, $entity->lft);
        $this->assertSame(22, $entity->rght);
        $this->assertNull($entity->parent_id);
        $result = $table->find()->order('lft')->enableHydration(false);
        $expected = [
            ' 1:18 -  1:electronics',
            '_ 2: 9 -  2:televisions',
            '__ 3: 4 -  3:tube',
            '__ 5: 6 -  4:lcd',
            '__ 7: 8 -  5:plasma',
            '_10:13 -  7:mp3',
            '__11:12 -  8:flash',
            '_14:15 -  9:cd',
            '_16:17 - 10:radios',
            '19:20 - 11:alien hardware',
            '21:22 -  6:portable',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests removing the root of a tree
     */
    public function testRemoveRootFromTree(): void
    {
        $table = $this->table;
        $entity = $table->get(1);
        $this->assertSame($entity, $table->removeFromTree($entity));
        $result = $table->find('threaded')->order('lft')->enableHydration(false)->toArray();
        $this->assertSame(21, $entity->lft);
        $this->assertSame(22, $entity->rght);
        $this->assertNull($entity->parent_id);

        $expected = [
            ' 1: 8 -  2:televisions',
            '_ 2: 3 -  3:tube',
            '_ 4: 5 -  4:lcd',
            '_ 6: 7 -  5:plasma',
            ' 9:18 -  6:portable',
            '_10:13 -  7:mp3',
            '__11:12 -  8:flash',
            '_14:15 -  9:cd',
            '_16:17 - 10:radios',
            '19:20 - 11:alien hardware',
            '21:22 -  1:electronics',
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests that using associations having tree fields in the schema
     * does not generate SQL errors
     */
    public function testFindPathWithAssociation(): void
    {
        $table = $this->table;
        $this->getTableLocator()->get('FriendlyTrees', [
            'table' => $table->getTable(),
        ]);
        $table->hasOne('FriendlyTrees', [
            'foreignKey' => 'id',
        ]);
        $result = $table
            ->find('children', ['for' => 1])
            ->contain('FriendlyTrees')
            ->toArray();
        $this->assertCount(9, $result);
    }

    /**
     * Tests getting the depth level of a node in the tree.
     */
    public function testGetLevel(): void
    {
        $entity = $this->table->get(8);
        $result = $this->table->getLevel($entity);
        $this->assertSame(3, $result);

        $result = $this->table->getLevel($entity->id);
        $this->assertSame(3, $result);

        $result = $this->table->getLevel(5);
        $this->assertSame(2, $result);

        $result = $this->table->getLevel(99999);
        $this->assertFalse($result);
    }

    /**
     * Test setting level for new nodes
     */
    public function testSetLevelNewNode(): void
    {
        $this->table->behaviors()->Tree->setConfig('level', 'depth');

        $entity = new Entity(['parent_id' => null, 'name' => 'Depth 0']);
        $this->table->save($entity);
        $entity = $this->table->get(12);
        $this->assertSame(0, $entity->depth);

        $entity = new Entity(['parent_id' => 1, 'name' => 'Depth 1']);
        $this->table->save($entity);
        $entity = $this->table->get(13);
        $this->assertSame(1, $entity->depth);

        $entity = new Entity(['parent_id' => 8, 'name' => 'Depth 4']);
        $this->table->save($entity);
        $entity = $this->table->get(14);
        $this->assertSame(4, $entity->depth);
    }

    /**
     * Test setting level for existing nodes
     */
    public function testSetLevelExistingNode(): void
    {
        $this->table->behaviors()->Tree->setConfig('level', 'depth');

        // Leaf node
        $entity = $this->table->get(4);
        $this->assertSame(2, $entity->depth);
        $this->table->save($entity);
        $entity = $this->table->get(4);
        $this->assertSame(2, $entity->depth);

        // Non leaf node so depth of descendants will also change
        $entity = $this->table->get(6);
        $this->assertSame(1, $entity->depth);

        $entity->parent_id = null;
        $this->table->save($entity);
        $entity = $this->table->get(6);
        $this->assertSame(0, $entity->depth);

        $entity = $this->table->get(7);
        $this->assertSame(1, $entity->depth);

        $entity = $this->table->get(8);
        $this->assertSame(2, $entity->depth);

        $entity->parent_id = 6;
        $this->table->save($entity);
        $entity = $this->table->get(8);
        $this->assertSame(1, $entity->depth);
    }

    /**
     * Assert MPTT values
     *
     * Custom assert method to make identifying the differences between expected
     * and actual db state easier to identify.
     *
     * @param array $expected tree state to be expected
     * @param \Cake\ORM\Table $table Table instance
     * @param \Cake\ORM\Query $query Optional query object
     */
    public function assertMpttValues($expected, $table, $query = null): void
    {
        $query = $query ?: $table->find();
        $primaryKey = $table->getPrimaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = $primaryKey[0];
        }
        $displayField = $table->getDisplayField();

        $options = [
            'valuePath' => function ($item, $key, $iterator) use ($primaryKey, $displayField) {
                return sprintf(
                    '%s:%s - %s:%s',
                    str_pad((string)$item->lft, 2, ' ', STR_PAD_LEFT),
                    str_pad((string)$item->rght, 2, ' ', STR_PAD_LEFT),
                    str_pad((string)$item->$primaryKey, 2, ' ', STR_PAD_LEFT),
                    $item->{$displayField}
                );
            },
        ];
        $result = array_values($query->find('treeList', $options)->toArray());

        if (count($result) === count($expected)) {
            $subExpected = array_diff($expected, $result);
            if ($subExpected) {
                $subResult = array_intersect_key($result, $subExpected);
                $this->assertSame($subExpected, $subResult, 'Differences in the tree were found (lft:rght id:display-name)');
            }
        }

        $this->assertSame($expected, $result, 'The tree is not the same (lft:rght id:display-name)');
    }
}
