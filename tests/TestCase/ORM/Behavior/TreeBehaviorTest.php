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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Translate behavior test case
 */
class TreeBehaviorTest extends TestCase
{

    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.menu_link_trees',
        'core.number_trees'
    ];

    public function setUp()
    {
        parent::setUp();
        $this->table = TableRegistry::get('NumberTrees');
        $this->table->primaryKey(['id']);
        $this->table->addBehavior('Tree');
    }

    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Sanity test
     *
     * Make sure the assert method acts as you'd expect, this is the expected
     * initial db state
     *
     * @return void
     */
    public function testAssertMpttValues()
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
            '21:22 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);

        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);

        $expected = [
            ' 1:10 -  1:Link 1',
            '_ 2: 3 -  2:Link 2',
            '_ 4: 9 -  3:Link 3',
            '__ 5: 8 -  4:Link 4',
            '___ 6: 7 -  5:Link 5',
            '11:14 -  6:Link 6',
            '_12:13 -  7:Link 7',
            '15:16 -  8:Link 8'
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
            '_18:19 - 18:radios'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests the find('path') method
     *
     * @return void
     */
    public function testFindPath()
    {
        $nodes = $this->table->find('path', ['for' => 9]);
        $this->assertEquals([1, 6, 9], $nodes->extract('id')->toArray());

        $nodes = $this->table->find('path', ['for' => 10]);
        $this->assertSame([1, 6, 10], $nodes->extract('id')->toArray());

        $nodes = $this->table->find('path', ['for' => 5]);
        $this->assertSame([1, 2, 5], $nodes->extract('id')->toArray());

        $nodes = $this->table->find('path', ['for' => 1]);
        $this->assertSame([1], $nodes->extract('id')->toArray());

        $entity = $this->table->newEntity(['name' => 'odd one', 'parent_id' => 1]);
        $entity = $this->table->save($entity);
        $newId = $entity->id;

        $entity = $this->table->get(2);
        $entity->parent_id = $newId;
        $this->table->save($entity);

        $nodes = $this->table->find('path', ['for' => 4]);
        $this->assertSame([1, $newId, 2, 4], $nodes->extract('id')->toArray());

        // find path with scope
        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $nodes = $table->find('path', ['for' => 5]);
        $this->assertSame([1, 3, 4, 5], $nodes->extract('id')->toArray());
    }

    /**
     * Tests the childCount() method
     *
     * @return void
     */
    public function testChildCount()
    {
        // direct children for the root node
        $table = $this->table;
        $countDirect = $this->table->childCount($table->get(1), true);
        $this->assertEquals(2, $countDirect);

        // counts all the children of root
        $count = $this->table->childCount($table->get(1), false);
        $this->assertEquals(9, $count);

        // counts direct children
        $count = $this->table->childCount($table->get(2), false);
        $this->assertEquals(3, $count);

        // count children for a middle-node
        $count = $this->table->childCount($table->get(6), false);
        $this->assertEquals(4, $count);

        // count leaf children
        $count = $this->table->childCount($table->get(10), false);
        $this->assertEquals(0, $count);

        // test scoping
        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $count = $table->childCount($table->get(3), false);
        $this->assertEquals(2, $count);
    }

    /**
     * Tests that childCount will provide the correct lft and rght values
     *
     * @return void
     */
    public function testChildCountNoTreeColumns()
    {
        $table = $this->table;
        $node = $table->get(6);
        $node->unsetProperty('lft');
        $node->unsetProperty('rght');
        $count = $this->table->childCount($node, false);
        $this->assertEquals(4, $count);
    }

    /**
     * Tests the childCount() plus callable scoping
     *
     * @return void
     */
    public function testCallableScoping()
    {
        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', [
            'scope' => function ($query) {
                return $query->where(['menu' => 'main-menu']);
            }
        ]);
        $count = $table->childCount($table->get(1), false);
        $this->assertEquals(4, $count);
    }

    /**
     * Tests the find('children') method
     *
     * @return void
     */
    public function testFindChildren()
    {
        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);

        // root
        $nodeIds = [];
        $nodes = $table->find('children', ['for' => 1])->all();
        $this->assertEquals([2, 3, 4, 5], $nodes->extract('id')->toArray());

        // leaf
        $nodeIds = [];
        $nodes = $table->find('children', ['for' => 5])->all();
        $this->assertEquals(0, count($nodes->extract('id')->toArray()));

        // direct children
        $nodes = $table->find('children', ['for' => 1, 'direct' => true])->all();
        $this->assertEquals([2, 3], $nodes->extract('id')->toArray());
    }

    /**
     * Tests that find('children') will throw an exception if the node was not found
     *
     * @expectedException \Cake\Datasource\Exception\RecordNotFoundException
     * @return void
     */
    public function testFindChildrenException()
    {
        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $query = $table->find('children', ['for' => 500]);
    }

    /**
     * Tests the find('treeList') method
     *
     * @return void
     */
    public function testFindTreeList()
    {
        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $result = $table->find('treeList')->toArray();
        $expected = [
            1 => 'Link 1',
            2 => '_Link 2',
            3 => '_Link 3',
            4 => '__Link 4',
            5 => '___Link 5',
            6 => 'Link 6',
            7 => '_Link 7',
            8 => 'Link 8'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the find('treeList') method after moveUp, moveDown
     *
     * @return void
     */
    public function testFindTreeListAfterMove()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '15:16 -  8:Link 8'
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
            '_14:15 -  7:Link 7'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests the find('treeList') method with custom options
     *
     * @return void
     */
    public function testFindTreeListCustom()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            'http://cakephp.org' => ' 7',
            '/page/who-we-are.html' => '8'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the testFormatTreeListCustom() method.
     *
     * @return void
     */
    public function testFormatTreeListCustom()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            'http://cakephp.org' => ' 7',
            '/page/who-we-are.html' => '8'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the moveUp() method
     *
     * @return void
     */
    public function testMoveUp()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '15:16 -  8:Link 8'
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
            '15:16 -  8:Link 8'
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
            '15:16 -  8:Link 8'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node with no siblings
     *
     * @return void
     */
    public function testMoveLeaf()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '15:16 -  8:Link 8'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node to the top
     *
     * @return void
     */
    public function testMoveTop()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '_14:15 -  7:Link 7'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node with no lft and rght
     *
     * @return void
     */
    public function testMoveNoTreeColumns()
    {
        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $node = $table->get(8);
        $node->unsetProperty('lft');
        $node->unsetProperty('rght');
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
            '_14:15 -  7:Link 7'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests the moveDown() method
     *
     * @return void
     */
    public function testMoveDown()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '15:16 -  8:Link 8'
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
            '15:16 -  8:Link 8'
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
            '15:16 -  8:Link 8'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node that has no siblings
     *
     * @return void
     */
    public function testMoveLeafDown()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '15:16 -  8:Link 8'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node to the bottom
     *
     * @return void
     */
    public function testMoveToBottom()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '___12:13 -  5:Link 5'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a node with no lft and rght columns
     *
     * @return void
     */
    public function testMoveDownNoTreeColumns()
    {
        $table = TableRegistry::get('MenuLinkTrees');
        $table->addBehavior('Tree', ['scope' => ['menu' => 'main-menu']]);
        $node = $table->get(1);
        $node->unsetProperty('lft');
        $node->unsetProperty('rght');
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
            '___12:13 -  5:Link 5'
        ];
        $this->assertMpttValues($expected, $table);
    }

    public function testMoveDownMultiplePositions()
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
            '21:22 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests the recover function
     *
     * @return void
     */
    public function testRecover()
    {
        $table = $this->table;

        $expectedLevels = $table
            ->find('list', ['valueField' => 'depth'])
            ->order('lft')
            ->toArray();
        $table->updateAll(['lft' => null, 'rght' => null, 'depth' => null], []);
        $table->behaviors()->Tree->config('level', 'depth');
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
            '21:22 - 11:alien hardware'
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
     *
     * @return void
     */
    public function testRecoverScoped()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '15:16 -  8:Link 8'
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
            '_18:19 - 18:radios'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Test recover function with a custom order clause
     *
     * @return void
     */
    public function testRecoverWithCustomOrder()
    {
        $table = TableRegistry::get('MenuLinkTrees');
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
            '_14:15 -  2:Link 2'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests adding a new orphan node
     *
     * @return void
     */
    public function testAddOrphan()
    {
        $table = $this->table;
        $entity = new Entity(
            ['name' => 'New Orphan', 'parent_id' => null, 'level' => null],
            ['markNew' => true]
        );
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(23, $entity->lft);
        $this->assertEquals(24, $entity->rght);

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
     * Tests that adding a child node as a decendant of one of the roots works
     *
     * @return void
     */
    public function testAddMiddle()
    {
        $table = $this->table;
        $entity = new Entity(
            ['name' => 'laptops', 'parent_id' => 1],
            ['markNew' => true]
        );
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(20, $entity->lft);
        $this->assertEquals(21, $entity->rght);

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
     *
     * @return void
     */
    public function testAddLeaf()
    {
        $table = $this->table;
        $entity = new Entity(
            ['name' => 'laptops', 'parent_id' => 2],
            ['markNew' => true]
        );
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(9, $entity->lft);
        $this->assertEquals(10, $entity->rght);

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
            '23:24 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests adding a root element to the tree when all other root elements have children
     *
     * @return void
     */
    public function testAddRoot()
    {
        $table = $this->table;

        //First add a child to the empty root element
        $alien = $table->find()->where(['name' => 'alien hardware'])->first();
        $entity = new Entity(['name' => 'plasma rifle', 'parent_id' => $alien->id], ['markNew' => true]);
        $table->save($entity);

        $entity = new Entity(['name' => 'carpentry', 'parent_id' => null], ['markNew' => true]);
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(25, $entity->lft);
        $this->assertEquals(26, $entity->rght);

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
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot set a node's parent as itself
     * @return void
     */
    public function testReParentSelf()
    {
        $entity = $this->table->get(1);
        $entity->parent_id = $entity->id;
        $this->table->save($entity);
    }

    /**
     * Tests making a node its own parent as a new entity.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot set a node's parent as itself
     * @return void
     */
    public function testReParentSelfNewEntity()
    {
        $entity = $this->table->newEntity(['name' => 'root']);
        $entity->id = 1;
        $entity->parent_id = $entity->id;
        $this->table->save($entity);
    }

    /**
     * Tests moving a subtree to the right
     *
     * @return void
     */
    public function testReParentSubTreeRight()
    {
        $table = $this->table;
        $entity = $table->get(2);
        $entity->parent_id = 6;
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(11, $entity->lft);
        $this->assertEquals(18, $entity->rght);

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
            '21:22 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a subtree to the left
     *
     * @return void
     */
    public function testReParentSubTreeLeft()
    {
        $table = $this->table;
        $entity = $table->get(6);
        $entity->parent_id = 2;
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(9, $entity->lft);
        $this->assertEquals(18, $entity->rght);

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
            '21:22 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Test moving a leaft to the left
     *
     * @return void
     */
    public function testReParentLeafLeft()
    {
        $table = $this->table;
        $entity = $table->get(10);
        $entity->parent_id = 2;
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(9, $entity->lft);
        $this->assertEquals(10, $entity->rght);

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
            '21:22 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Test moving a leaf to the left
     *
     * @return void
     */
    public function testReParentLeafRight()
    {
        $table = $this->table;
        $entity = $table->get(5);
        $entity->parent_id = 6;
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(17, $entity->lft);
        $this->assertEquals(18, $entity->rght);

        $result = $table->find()->order('lft')->hydrate(false);

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
            '21:22 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a subtree with a node having no lft and rght columns
     *
     * @return void
     */
    public function testReParentNoTreeColumns()
    {
        $table = $this->table;
        $entity = $table->get(6);
        $entity->unsetProperty('lft');
        $entity->unsetProperty('rght');
        $entity->parent_id = 2;
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(9, $entity->lft);
        $this->assertEquals(18, $entity->rght);

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
            '21:22 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests moving a subtree as a new root
     *
     * @return void
     */
    public function testRootingSubTree()
    {
        $table = $this->table;
        $entity = $table->get(2);
        $entity->parent_id = null;
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(15, $entity->lft);
        $this->assertEquals(22, $entity->rght);

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
            '_20:21 -  5:plasma'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests moving a subtree with no tree columns
     *
     * @return void
     */
    public function testRootingNoTreeColumns()
    {
        $table = $this->table;
        $entity = $table->get(2);
        $entity->unsetProperty('lft');
        $entity->unsetProperty('rght');
        $entity->parent_id = null;
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals(15, $entity->lft);
        $this->assertEquals(22, $entity->rght);

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
            '_20:21 -  5:plasma'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests that trying to create a cycle throws an exception
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot use node "5" as parent for entity "2"
     * @return void
     */
    public function testReparentCycle()
    {
        $table = $this->table;
        $entity = $table->get(2);
        $entity->parent_id = 5;
        $table->save($entity);
    }

    /**
     * Tests deleting a leaf in the tree
     *
     * @return void
     */
    public function testDeleteLeaf()
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
            '19:20 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests deleting a subtree
     *
     * @return void
     */
    public function testDeleteSubTree()
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
            '11:12 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Test deleting a root node
     *
     * @return void
     */
    public function testDeleteRoot()
    {
        $table = $this->table;
        $entity = $table->get(1);
        $this->assertTrue($table->delete($entity));

        $expected = [
            ' 1: 2 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Test deleting a node with no tree columns
     *
     * @return void
     */
    public function testDeleteRootNoTreeColumns()
    {
        $table = $this->table;
        $entity = $table->get(1);
        $entity->unsetProperty('lft');
        $entity->unsetProperty('rght');
        $this->assertTrue($table->delete($entity));

        $expected = [
            ' 1: 2 - 11:alien hardware'
        ];
        $this->assertMpttValues($expected, $this->table);
    }

    /**
     * Tests that a leaf can be taken out of the tree and put in as a root
     *
     * @return void
     */
    public function testRemoveFromLeafFromTree()
    {
        $table = $this->table;
        $entity = $table->get(10);
        $this->assertSame($entity, $table->removeFromTree($entity));
        $this->assertEquals(21, $entity->lft);
        $this->assertEquals(22, $entity->rght);
        $this->assertEquals(null, $entity->parent_id);
        $result = $table->find()->order('lft')->hydrate(false);
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
            '21:22 - 10:radios'

        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Test removing a middle node from a tree
     *
     * @return void
     */
    public function testRemoveMiddleNodeFromTree()
    {
        $table = $this->table;
        $entity = $table->get(6);
        $this->assertSame($entity, $table->removeFromTree($entity));
        $result = $table->find('threaded')->order('lft')->hydrate(false)->toArray();
        $this->assertEquals(21, $entity->lft);
        $this->assertEquals(22, $entity->rght);
        $this->assertEquals(null, $entity->parent_id);
        $result = $table->find()->order('lft')->hydrate(false);
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
            '21:22 -  6:portable'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests removing the root of a tree
     *
     * @return void
     */
    public function testRemoveRootFromTree()
    {
        $table = $this->table;
        $entity = $table->get(1);
        $this->assertSame($entity, $table->removeFromTree($entity));
        $result = $table->find('threaded')->order('lft')->hydrate(false)->toArray();
        $this->assertEquals(21, $entity->lft);
        $this->assertEquals(22, $entity->rght);
        $this->assertEquals(null, $entity->parent_id);

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
            '21:22 -  1:electronics'
        ];
        $this->assertMpttValues($expected, $table);
    }

    /**
     * Tests that using associations having tree fields in the schema
     * does not generate SQL errors
     *
     * @return void
     */
    public function testFindPathWithAssociation()
    {
        $table = $this->table;
        $other = TableRegistry::get('FriendlyTrees', [
            'table' => $table->table()
        ]);
        $table->hasOne('FriendlyTrees', [
            'foreignKey' => 'id'
        ]);
        $result = $table
            ->find('children', ['for' => 1])
            ->contain('FriendlyTrees')
            ->toArray();
        $this->assertCount(9, $result);
    }

    /**
     * Tests getting the depth level of a node in the tree.
     *
     * @return void
     */
    public function testGetLevel()
    {
        $entity = $this->table->get(8);
        $result = $this->table->getLevel($entity);
        $this->assertEquals(3, $result);

        $result = $this->table->getLevel($entity->id);
        $this->assertEquals(3, $result);

        $result = $this->table->getLevel(5);
        $this->assertEquals(2, $result);

        $result = $this->table->getLevel(99999);
        $this->assertFalse($result);
    }

    /**
     * Test setting level for new nodes
     *
     * @return void
     */
    public function testSetLevelNewNode()
    {
        $this->table->behaviors()->Tree->config('level', 'depth');

        $entity = new Entity(['parent_id' => null, 'name' => 'Depth 0']);
        $this->table->save($entity);
        $entity = $this->table->get(12);
        $this->assertEquals(0, $entity->depth);

        $entity = new Entity(['parent_id' => 1, 'name' => 'Depth 1']);
        $this->table->save($entity);
        $entity = $this->table->get(13);
        $this->assertEquals(1, $entity->depth);

        $entity = new Entity(['parent_id' => 8, 'name' => 'Depth 4']);
        $this->table->save($entity);
        $entity = $this->table->get(14);
        $this->assertEquals(4, $entity->depth);
    }

    /**
     * Test setting level for existing nodes
     *
     * @return void
     */
    public function testSetLevelExistingNode()
    {
        $this->table->behaviors()->Tree->config('level', 'depth');

        // Leaf node
        $entity = $this->table->get(4);
        $this->assertEquals(2, $entity->depth);
        $this->table->save($entity);
        $entity = $this->table->get(4);
        $this->assertEquals(2, $entity->depth);

        // Non leaf node so depth of descendents will also change
        $entity = $this->table->get(6);
        $this->assertEquals(1, $entity->depth);

        $entity->parent_id = null;
        $this->table->save($entity);
        $entity = $this->table->get(6);
        $this->assertEquals(0, $entity->depth);

        $entity = $this->table->get(7);
        $this->assertEquals(1, $entity->depth);

        $entity = $this->table->get(8);
        $this->assertEquals(2, $entity->depth);

        $entity->parent_id = 6;
        $this->table->save($entity);
        $entity = $this->table->get(8);
        $this->assertEquals(1, $entity->depth);
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
     * @return void
     */
    public function assertMpttValues($expected, $table, $query = null)
    {
        $query = $query ?: $table->find();
        $primaryKey = $table->primaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = $primaryKey[0];
        }
        $displayField = $table->displayField();

        $options = [
            'valuePath' => function ($item, $key, $iterator) use ($primaryKey, $displayField) {
                return sprintf(
                    '%s:%s - %s:%s',
                    str_pad($item->lft, 2, ' ', STR_PAD_LEFT),
                    str_pad($item->rght, 2, ' ', STR_PAD_LEFT),
                    str_pad($item->$primaryKey, 2, ' ', STR_PAD_LEFT),
                    $item->{$displayField}
                );
            }
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
