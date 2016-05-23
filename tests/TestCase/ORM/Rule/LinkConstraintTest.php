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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Rule;

use Cake\Core\Configure;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\RulesChecker;
use Cake\ORM\Rule\LinkConstraint;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * LinkConstraintTest class test.
 */
class LinkConstraintTest extends TestCase
{
    public $fixtures = [
        'core.articles',
        'core.articles_tags',
        'core.authors',
        'core.comments',
        'core.tags',
        'core.users'
    ];

    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
    }

    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    public function invalidConstructorArgumentDataProvider()
    {
        return [[''], [null], [1], [[]]];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument 1 is expected to be either an instance of `Cake\ORM\Association`, or a non-empty string.
     *
     * @dataProvider invalidConstructorArgumentDataProvider
     * @param mixed $value
     */
    public function testInvalidConstructorArgumentOne($value)
    {
        new LinkConstraint($value, LinkConstraint::LINK_STATUS_LINKED);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument 2 is expected to match one of the `\Cake\ORM\Rule\LinkConstraint::LINK_STATUS_*` constants.
     *
     * @dataProvider invalidConstructorArgumentDataProvider
     * @param mixed $value
     */
    public function testInvalidConstructorArgumentTwo($value)
    {
        new LinkConstraint('Association', $value);
    }

    public function invalidConstructorArgumentThreeDataProvider()
    {
        return [[''], [1], [[]]];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument 3 is expected to match one of the `\Cake\ORM\Rule\LinkConstraint::ERROR_MODE_*` constants.
     *
     * @dataProvider invalidConstructorArgumentThreeDataProvider
     * @param mixed $value
     */
    public function testInvalidConstructorArgumentThree($value)
    {
        new LinkConstraint('Association', LinkConstraint::LINK_STATUS_LINKED, $value);
    }

    public function validConstructorArgumentOneDataProvider()
    {
        return [['Association'], [new BelongsTo('Association')]];
    }

    /**
     * @dataProvider validConstructorArgumentOneDataProvider
     * @param mixed $value
     */
    public function testValidConstructorArgumentOne($value)
    {
        new LinkConstraint($value, LinkConstraint::LINK_STATUS_LINKED);
    }

    public function validConstructorArgumentTwoDataProvider()
    {
        return [[LinkConstraint::LINK_STATUS_LINKED], [LinkConstraint::LINK_STATUS_NOT_LINKED]];
    }

    /**
     * @dataProvider validConstructorArgumentTwoDataProvider
     * @param mixed $value
     */
    public function testValidConstructorArgumentTwo($value)
    {
        new LinkConstraint('Association', $value);
    }

    public function validConstructorArgumentThreeDataProvider()
    {
        return [[LinkConstraint::ERROR_MODE_EXCEPTIONS], [LinkConstraint::ERROR_MODE_RETURN_VALUE]];
    }

    /**
     * @dataProvider validConstructorArgumentThreeDataProvider
     * @param mixed $value
     */
    public function testValidConstructorArgumentThree($value)
    {
        new LinkConstraint('Association', LinkConstraint::LINK_STATUS_LINKED, $value);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The association `NonExistent` could not be found on the repository `Articles`.
     */
    public function testNonExistentAssociation()
    {
        $Articles = TableRegistry::get('Articles');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('NonExistent', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The number of fields is expected to match the number of values.
     */
    public function testNonMatchingCompositeKeys()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasMany('Comments')->foreignKey(['id', 'article_id']);

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    public function invalidRepositoryOptionsDataProvider()
    {
        return [
            [['repository' => null]],
            [['repository' => new \stdClass()]],
            [[]]
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument 2 is expected to have a `repository` key set that holds an instance of `\Cake\ORM\Table`
     *
     * @dataProvider invalidRepositoryOptionsDataProvider
     * @param mixed $options
     */
    public function testInvalidRepository($options)
    {
        $Articles = $this->getMockForModel('Articles', ['buildRules'], ['table' => 'articles']);

        $rulesChecker = new RulesChecker($options);
        $Articles->expects($this->atLeastOnce())->method('buildRules')->willReturn($rulesChecker);

        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );
        $Articles->buildRules($rulesChecker);

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    public function testReturnValueErrorModeWithMustNotBeLinkedViaBelongsToIsLinked()
    {
        $Comments = TableRegistry::get('Comments');
        $Comments->belongsTo('Articles');

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_NOT_LINKED, LinkConstraint::ERROR_MODE_RETURN_VALUE),
            '_isNotLinked',
            [
                'errorField' => 'articles'
            ]
        );

        $comment = $Comments->get(1);
        $this->assertFalse($Comments->delete($comment));

        $expected = [
            'articles' => [
                '_isNotLinked' => 'invalid'
            ]
        ];
        $this->assertEquals($expected, $comment->errors());
    }

    public function testMustBeLinkedViaBelongsToIsLinked()
    {
        $Comments = TableRegistry::get('Comments');
        $Comments->belongsTo('Articles');

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_LINKED)
        );

        $comment = $Comments->get(1);
        $comment->dirty('comment', true);
        $this->assertNotFalse($Comments->save($comment));
    }

    public function testUsingAssociationInstanceMustBeLinkedViaBelongsToIsLinked()
    {
        $Comments = TableRegistry::get('Comments');
        $Comments->belongsTo('Articles');

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint($Comments->association('Articles'), LinkConstraint::LINK_STATUS_LINKED)
        );

        $comment = $Comments->get(1);
        $comment->dirty('comment', true);
        $this->assertNotFalse($Comments->save($comment));
    }

    /**
     * @expectedException \Cake\ORM\Exception\LinkConstraintViolationException
     * @expectedExceptionMessage Cannot modify row: a constraint for the `Comments` repositories `Articles` association fails
     */
    public function testMustBeLinkedViaBelongsToIsNotLinked()
    {
        $Comments = TableRegistry::get('Comments');
        $Comments->belongsTo('Articles');

        $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment'
        ]));

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_LINKED)
        );

        $comment = $Comments->get(7);
        $comment->dirty('comment', true);
        $Comments->save($comment);
    }

    public function testMustBeLinkedViaBelongsManyToIsLinked()
    {
        $Tags = TableRegistry::get('Tags');

        $rulesChecker = $Tags->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_LINKED)
        );

        $tag = $Tags->get(1);
        $tag->dirty('name', true);
        $this->assertNotFalse($Tags->save($tag));
    }

    /**
     * @expectedException \Cake\ORM\Exception\LinkConstraintViolationException
     * @expectedExceptionMessage Cannot modify row: a constraint for the `Tags` repositories `articles` association fails
     */
    public function testMustBeLinkedViaBelongsToManyIsNotLinked()
    {
        $Tags = TableRegistry::get('Tags');

        $Tags->save($Tags->newEntity([
            'name' => 'Orphaned Tag'
        ]));

        $rulesChecker = $Tags->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_LINKED)
        );

        $tag = $Tags->get(4);
        $tag->dirty('name', true);
        $Tags->save($tag);
    }

    public function testMustBeLinkedViaHasManyIsLinked()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasMany('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_LINKED)
        );

        $article = $Articles->get(1);
        $article->dirty('comment', true);
        $this->assertNotFalse($Articles->save($article));
    }

    /**
     * @expectedException \Cake\ORM\Exception\LinkConstraintViolationException
     * @expectedExceptionMessage Cannot modify row: a constraint for the `Articles` repositories `Comments` association fails
     */
    public function testMustBeLinkedViaHasManyIsNotLinked()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasMany('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_LINKED)
        );

        $article = $Articles->get(3);
        $article->dirty('comment', true);
        $Articles->save($article);
    }

    public function testMustBeLinkedViaHasOneIsLinked()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasOne('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_LINKED)
        );

        $article = $Articles->get(1);
        $article->dirty('title', true);
        $this->assertNotFalse($Articles->save($article));
    }

    /**
     * @expectedException \Cake\ORM\Exception\LinkConstraintViolationException
     * @expectedExceptionMessage Cannot modify row: a constraint for the `Articles` repositories `Comments` association fails
     */
    public function testMustBeLinkedViaHasOneIsNotLinked()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasOne('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_LINKED)
        );

        $article = $Articles->get(3);
        $article->dirty('title', true);
        $Articles->save($article);
    }

    /**
     * @expectedException \Cake\ORM\Exception\LinkConstraintViolationException
     * @expectedExceptionMessage Cannot modify row: a constraint for the `Comments` repositories `Articles` association fails
     */
    public function testMustNotBeLinkedViaBelongsToIsLinked()
    {
        $Comments = TableRegistry::get('Comments');
        $Comments->belongsTo('Articles');

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $comment = $Comments->get(1);
        $Comments->delete($comment);
    }

    public function testMustNotBeLinkedViaBelongsToIsNotLinked()
    {
        $Comments = TableRegistry::get('Comments');
        $Comments->belongsTo('Articles');

        $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment'
        ]));

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $comment = $Comments->get(7);
        $this->assertTrue($Comments->delete($comment));
    }

    /**
     * @expectedException \Cake\ORM\Exception\LinkConstraintViolationException
     * @expectedExceptionMessage Cannot modify row: a constraint for the `Tags` repositories `articles` association fails
     */
    public function testMustNotBeLinkedViaBelongsToManyIsLinked()
    {
        $Tags = TableRegistry::get('Tags');

        $rulesChecker = $Tags->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $tag = $Tags->get(1);
        $Tags->delete($tag);
    }

    public function testMustNotBeLinkedViaBelongsToManyIsNotLinked()
    {
        $Tags = TableRegistry::get('Tags');

        $Tags->save($Tags->newEntity([
            'name' => 'Orphaned Tag'
        ]));

        $rulesChecker = $Tags->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $tag = $Tags->get(4);
        $this->assertTrue($Tags->delete($tag));
    }

    /**
     * @expectedException \Cake\ORM\Exception\LinkConstraintViolationException
     * @expectedExceptionMessage Cannot modify row: a constraint for the `Articles` repositories `Comments` association fails
     */
    public function testMustNotBeLinkedViaHasManyIsLinked()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasMany('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    public function testMustNotBeLinkedViaHasManyIsNotLinked()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasMany('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $article = $Articles->get(3);
        $this->assertTrue($Articles->delete($article));
    }

    /**
     * @expectedException \Cake\ORM\Exception\LinkConstraintViolationException
     * @expectedExceptionMessage Cannot modify row: a constraint for the `Articles` repositories `Comments` association fails
     */
    public function testMustNotBeLinkedViaHasOneIsLinked()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasOne('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    public function testMustNotBeLinkedViaHasOneIsNotLinked()
    {
        $Articles = TableRegistry::get('Articles');
        $Articles->hasOne('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::LINK_STATUS_NOT_LINKED)
        );

        $article = $Articles->get(3);
        $this->assertTrue($Articles->delete($article));
    }
}
