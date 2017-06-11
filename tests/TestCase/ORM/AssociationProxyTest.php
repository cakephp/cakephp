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

use Cake\ORM\Association;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests the features related to proxying methods from the Association
 * class to the Table class
 */
class AssociationProxyTest extends TestCase
{

    /**
     * Fixtures to be loaded
     *
     * @var array
     */
    public $fixtures = [
        'core.articles', 'core.authors', 'core.comments'
    ];

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Tests that it is possible to get associations as a property
     *
     * @return void
     */
    public function testAssociationAsProperty()
    {
        $articles = TableRegistry::get('articles');
        $articles->hasMany('comments');
        $articles->belongsTo('authors');
        $this->assertTrue(isset($articles->authors));
        $this->assertTrue(isset($articles->comments));
        $this->assertFalse(isset($articles->posts));
        $this->assertSame($articles->association('authors'), $articles->authors);
        $this->assertSame($articles->association('comments'), $articles->comments);
    }

    /**
     * Tests that getting a bad property throws exception
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Table "Cake\ORM\Table" is not associated with "posts"
     * @return void
     */
    public function testGetBadAssociation()
    {
        $articles = TableRegistry::get('articles');
        $articles->posts;
    }

    /**
     * Test that find() with empty conditions generates valid SQL
     *
     * @return void
     */
    public function testFindEmptyConditions()
    {
        $table = TableRegistry::get('Users');
        $table->hasMany('Articles', [
            'foreignKey' => 'author_id',
            'conditions' => '',
        ]);
        $query = $table->Articles->find('list', ['limit' => 2]);
        $this->assertCount(2, $query->all());
    }

    /**
     * Tests that the proxied updateAll will preserve conditions set for the association
     *
     * @return void
     */
    public function testUpdateAllFromAssociation()
    {
        $articles = TableRegistry::get('articles');
        $comments = TableRegistry::get('comments');
        $articles->hasMany('comments', ['conditions' => ['published' => 'Y']]);
        $articles->comments->updateAll(['comment' => 'changed'], ['article_id' => 1]);
        $changed = $comments->find()->where(['comment' => 'changed'])->count();
        $this->assertEquals(3, $changed);
    }

    /**
     * Tests that the proxied deleteAll preserves conditions set for the association
     *
     * @return void
     */
    public function testDeleteAllFromAssociation()
    {
        $articles = TableRegistry::get('articles');
        $comments = TableRegistry::get('comments');
        $articles->hasMany('comments', ['conditions' => ['published' => 'Y']]);
        $articles->comments->deleteAll(['article_id' => 1]);
        $remaining = $comments->find()->where(['article_id' => 1])->count();
        $this->assertEquals(1, $remaining);
    }

    /**
     * Tests that it is possible to get associations as a property
     *
     * @return void
     */
    public function testAssociationAsPropertyProxy()
    {
        $articles = TableRegistry::get('articles');
        $authors = TableRegistry::get('authors');
        $articles->belongsTo('authors');
        $authors->hasMany('comments');
        $this->assertTrue(isset($articles->authors->comments));
        $this->assertSame($authors->association('comments'), $articles->authors->comments);
    }

    /**
     * Tests that methods are proxied from the Association to the target table
     *
     * @return void
     */
    public function testAssociationMethodProxy()
    {
        $articles = TableRegistry::get('articles');
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['crazy'])
            ->getMock();
        $articles->belongsTo('authors', [
            'targetTable' => $mock
        ]);

        $mock->expects($this->once())->method('crazy')
            ->with('a', 'b')
            ->will($this->returnValue('thing'));
        $this->assertEquals('thing', $articles->authors->crazy('a', 'b'));
    }
}
