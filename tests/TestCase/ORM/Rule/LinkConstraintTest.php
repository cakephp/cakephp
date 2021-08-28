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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Rule;

use Cake\Core\Configure;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Query;
use Cake\ORM\Rule\LinkConstraint;
use Cake\ORM\RulesChecker;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use RuntimeException;
use stdClass;

/**
 * Tests the LinkConstraint rule.
 */
class LinkConstraintTest extends TestCase
{
    /**
     * Fixtures.
     *
     * @var string[]
     */
    protected $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
        'core.Attachments',
        'core.Comments',
    ];

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * Tear down.
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->getTableLocator()->clear();
    }

    /**
     * Data provider for invalid constructor argument.
     *
     * @return array
     */
    public function invalidConstructorArgumentOneDataProvider(): array
    {
        return [[null, 'NULL'], [1, 'integer'], [[], 'array'], [new \stdClass(), 'stdClass']];
    }

    /**
     * Tests that an exception is thrown when passing an invalid value for the `$association` argument.
     *
     * @dataProvider invalidConstructorArgumentOneDataProvider
     * @param mixed $value
     * @param string $actualType
     */
    public function testInvalidConstructorArgumentOne($value, $actualType): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Argument 1 is expected to be of type `\Cake\ORM\Association|string`, `%s` given.',
            $actualType
        ));

        new LinkConstraint($value, LinkConstraint::STATUS_LINKED);
    }

    /**
     * Tests that an exception is thrown when passing an invalid value for the `$requiredLinkStatus` argument.
     *
     * @dataProvider invalidConstructorArgumentOneDataProvider
     */
    public function testInvalidConstructorArgumentTwo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument 2 is expected to match one of the `\Cake\ORM\Rule\LinkConstraint::STATUS_*` constants.');

        new LinkConstraint('Association', 'invalid');
    }

    /**
     * Tests that an exception is thrown when an association with the given name doesn't exist.
     */
    public function testNonExistentAssociation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `NonExistent` association is not defined on `Articles`.');

        $Articles = $this->getTableLocator()->get('Articles');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('NonExistent', LinkConstraint::STATUS_NOT_LINKED)
        );

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    /**
     * Tests that an exception is thrown when the checked entity doesn't contain all primary key values.
     */
    public function testMissingPrimaryKeyValues(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'LinkConstraint rule on `Articles` requires all primary key values for building the counting ' .
            'conditions, expected values for `(id, nonexistent)`, got `(1, )`.'
        );

        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        $Articles->getEventManager()->on('Model.beforeRules', function (Event $event): void {
            $event->getSubject()->setPrimaryKey(['id', 'nonexistent']);
        });

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED)
        );

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    /**
     * Tests that an exception is thrown when the number of the extracted primary keys in the check entity doesn't
     * match the required number of primary key parts.
     */
    public function testNonMatchingKeyFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The number of fields is expected to match the number of values, got 0 field(s) and 1 value(s).'
        );

        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments')->setForeignKey(['id', 'article_id']);

        /** @var \Cake\ORM\Rule\LinkConstraint|\PHPUnit\Framework\MockObject\MockObject $ruleMock */
        $ruleMock = $this
            ->getMockBuilder(LinkConstraint::class)
            ->setConstructorArgs(['Comments', LinkConstraint::STATUS_NOT_LINKED])
            ->onlyMethods(['_aliasFields'])
            ->getMock();
        $ruleMock
            ->expects($this->once())
            ->method('_aliasFields')
            ->willReturn([]);

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete($ruleMock);

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    /**
     * Data provider for invalid `repository` option.
     *
     * @return array
     */
    public function invalidRepositoryOptionsDataProvider(): array
    {
        return [
            [['repository' => null]],
            [['repository' => new stdClass()]],
            [[]],
        ];
    }

    /**
     * Tests that an exception is thrown when the `repository` option holds an invalid value.
     *
     * @dataProvider invalidRepositoryOptionsDataProvider
     * @param mixed $options
     */
    public function testInvalidRepository($options): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument 2 is expected to have a `repository` key that holds an instance of `\Cake\ORM\Table`');

        $Articles = $this->getMockForModel('Articles', ['buildRules'], ['table' => 'articles']);

        $rulesChecker = new RulesChecker($options);
        $Articles->expects($this->atLeastOnce())->method('buildRules')->willReturn($rulesChecker);

        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED)
        );
        $Articles->buildRules($rulesChecker);

        $article = $Articles->get(1);
        $Articles->delete($article);
    }

    /**
     * Tests that the rule succeeds when a required `belongsTo` link exists.
     */
    public function testMustBeLinkedViaBelongsToIsLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Articles', LinkConstraint::STATUS_LINKED)
        );

        $comment = $Comments->get(1);
        $comment->setDirty('comment', true);
        $this->assertNotFalse($Comments->save($comment));
        $this->assertEmpty($comment->getErrors());
    }

    /**
     * Tests that the rule fails when a required `belongsTo` link does not exist.
     */
    public function testMustBeLinkedViaBelongsToIsNotLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Articles', LinkConstraint::STATUS_LINKED),
            '_isLinkedTo',
            [
                'errorField' => 'article',
            ]
        );

        $comment = $Comments->get(7);
        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'article' => [
                '_isLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests that the rule succeeds when a required `belongsToMany` link exists.
     */
    public function testMustBeLinkedViaBelongsToManyToIsLinked(): void
    {
        $Tags = $this->getTableLocator()->get('Tags');

        $rulesChecker = $Tags->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Articles', LinkConstraint::STATUS_LINKED)
        );

        $tag = $Tags->get(1);
        $tag->setDirty('name', true);
        $this->assertNotFalse($Tags->save($tag));
        $this->assertEmpty($tag->getErrors());
    }

    /**
     * Tests that the rule fails when a required `belongsToMany` link does not exist.
     */
    public function testMustBeLinkedViaBelongsToManyIsNotLinked(): void
    {
        $Tags = $this->getTableLocator()->get('Tags');

        $Tags->save($Tags->newEntity([
            'name' => 'Orphaned Tag',
        ]));

        $rulesChecker = $Tags->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Articles', LinkConstraint::STATUS_LINKED),
            '_isLinkedTo',
            [
                'errorField' => 'articles',
            ]
        );

        $tag = $Tags->get(4);
        $tag->setDirty('name', true);
        $this->assertFalse($Tags->save($tag));

        $expected = [
            'articles' => [
                '_isLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $tag->getErrors());
    }

    /**
     * Tests that the rule succeeds when a required `hasMany` link exists.
     */
    public function testMustBeLinkedViaHasManyIsLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Comments', LinkConstraint::STATUS_LINKED)
        );

        $article = $Articles->get(1);
        $article->setDirty('comment', true);
        $this->assertNotFalse($Articles->save($article));
        $this->assertEmpty($article->getErrors());
    }

    /**
     * Tests that the rule fails when a required `hasMany` link does not exist.
     */
    public function testMustBeLinkedViaHasManyIsNotLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Comments', LinkConstraint::STATUS_LINKED),
            '_isLinkedTo',
            [
                'errorField' => 'comments',
            ]
        );

        $article = $Articles->get(3);
        $article->setDirty('comment', true);
        $this->assertFalse($Articles->save($article));

        $expected = [
            'comments' => [
                '_isLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that the rule succeeds when a required `hasOne` link exists.
     */
    public function testMustBeLinkedViaHasOneIsLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasOne('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Comments', LinkConstraint::STATUS_LINKED)
        );

        $article = $Articles->get(1);
        $article->setDirty('title', true);
        $this->assertNotFalse($Articles->save($article));
        $this->assertEmpty($article->getErrors());
    }

    /**
     * Tests that the rule fails when a required `hasOne` link does not exist.
     */
    public function testMustBeLinkedViaHasOneIsNotLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasOne('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint('Comments', LinkConstraint::STATUS_LINKED),
            '_isLinkedTo',
            [
                'errorField' => 'comment',
            ]
        );

        $article = $Articles->get(3);
        $article->setDirty('title', true);
        $this->assertFalse($Articles->save($article));

        $expected = [
            'comment' => [
                '_isLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that the rule succeeds when a prohibited `belongsTo` link does not exist.
     */
    public function testMustNotBeLinkedViaBelongsToIsNotLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::STATUS_NOT_LINKED)
        );

        $comment = $Comments->get(7);
        $this->assertTrue($Comments->delete($comment));
        $this->assertEmpty($comment->getErrors());
    }

    /**
     * Tests that the rule fails when a prohibited `belongsTo` link exists.
     */
    public function testMustNotBeLinkedViaBelongsToIsLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'article',
            ]
        );

        $comment = $Comments->get(1);
        $this->assertFalse($Comments->delete($comment));

        $expected = [
            'article' => [
                '_isNotLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests that the rule succeeds when a prohibited `belongsToMany` link does not exist.
     */
    public function testMustNotBeLinkedViaBelongsToManyIsNotLinked(): void
    {
        $Tags = $this->getTableLocator()->get('Tags');

        $Tags->save($Tags->newEntity([
            'name' => 'Orphaned Tag',
        ]));

        $rulesChecker = $Tags->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::STATUS_NOT_LINKED)
        );

        $tag = $Tags->get(4);
        $this->assertTrue($Tags->delete($tag));
        $this->assertEmpty($tag->getErrors());
    }

    /**
     * Tests that the rule fails when a prohibited `belongsToMany` link exists.
     */
    public function testMustNotBeLinkedViaBelongsToManyIsLinked(): void
    {
        $Tags = $this->getTableLocator()->get('Tags');

        $rulesChecker = $Tags->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'articles',
            ]
        );

        $tag = $Tags->get(1);
        $this->assertFalse($Tags->delete($tag));

        $expected = [
            'articles' => [
                '_isNotLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $tag->getErrors());
    }

    /**
     * Tests that the rule succeeds when a prohibited `hasMany` link does not exist.
     */
    public function testMustNotBeLinkedViaHasManyIsNotLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED)
        );

        $article = $Articles->get(3);
        $this->assertTrue($Articles->delete($article));
        $this->assertEmpty($article->getErrors());
    }

    /**
     * Tests that the rule fails when a prohibited `hasMany` link exists.
     */
    public function testMustNotBeLinkedViaHasManyIsLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'comments',
            ]
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comments' => [
                '_isNotLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that the rule succeeds when a prohibited `hasOne` link does not exist.
     */
    public function testMustNotBeLinkedViaHasOneIsNotLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasOne('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED)
        );

        $article = $Articles->get(3);
        $this->assertTrue($Articles->delete($article));
        $this->assertEmpty($article->getErrors());
    }

    /**
     * Tests that the rule fails when a prohibited `hasOne` link exists.
     */
    public function testMustNotBeLinkedViaHasOneIsLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasOne('Comments');

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'comment',
            ]
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comment' => [
                '_isNotLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that using associations with disabled foreign keys and expression conditions works.
     */
    public function testDisabledForeignKeyAndSubQueryConditionsWithMustNotBeLinkedIsNotLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasOne('Comments', [
            'foreignKey' => false,
            'conditions' => function (QueryExpression $exp, Query $query) {
                $connection = $query->getConnection();
                $subQuery = $connection
                    ->newQuery()
                    ->select(['RecentComments.id'])
                    ->from(['RecentComments' => 'comments'])
                    ->where(function (QueryExpression $exp) {
                        return $exp->eq(
                            new IdentifierExpression('Articles.id'),
                            new IdentifierExpression('RecentComments.article_id')
                        );
                    })
                    ->order(['RecentComments.created' => 'DESC'])
                    ->limit(1);

                return $exp->add(['Comments.id' => $subQuery]);
            },
        ]);

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED)
        );

        $article = $Articles->get(3);
        $this->assertTrue($Articles->delete($article));
        $this->assertEmpty($article->getErrors());
    }

    /**
     * Tests that using associations with disabled foreign keys and expression conditions works.
     */
    public function testDisabledForeignKeyAndSubQueryConditionsWithMustNotBeLinkedIsLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasOne('Comments', [
            'foreignKey' => false,
            'conditions' => function (QueryExpression $exp, Query $query) {
                $connection = $query->getConnection();
                $subQuery = $connection
                    ->newQuery()
                    ->select(['RecentComments.id'])
                    ->from(['RecentComments' => 'comments'])
                    ->where(function (QueryExpression $exp) {
                        return $exp->eq(
                            new IdentifierExpression('Articles.id'),
                            new IdentifierExpression('RecentComments.article_id')
                        );
                    })
                    ->order(['RecentComments.created' => 'DESC'])
                    ->limit(1);

                return $exp->add(['Comments.id' => $subQuery]);
            },
        ]);

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'comment',
            ]
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comment' => [
                '_isNotLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that using associations with array conditions works.
     */
    public function testConditionsWithMustNotBeLinkedIsNotLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments', [
            'conditions' => [
                'Comments.published' => 'N',
            ],
        ]);

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED)
        );

        $article = $Articles->get(2);
        $this->assertTrue($Articles->delete($article));
        $this->assertEmpty($article->getErrors());
    }

    /**
     * Tests that using associations with array conditions works.
     */
    public function testConditionsWithMustNotBeLinkedIsLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments', [
            'conditions' => [
                'Comments.published' => 'Y',
            ],
        ]);

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'comments',
            ]
        );

        $article = $Articles->get(2);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comments' => [
                '_isNotLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that using associations with conditions that are referencing the main table works.
     */
    public function testConditionsReferencingParentColumnWithMustNotBeLinkedIsNotLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasOne('Comments', [
            'conditions' => function (QueryExpression $exp) {
                return $exp->notEq(
                    new IdentifierExpression('Comments.published'),
                    new IdentifierExpression('Articles.published')
                );
            },
        ]);

        $article = $Articles->save($Articles->newEntity([
            'user_id' => 1,
            'body' => 'Some Text',
            'published' => 'N',
            'comment' => [
                'user_id' => 1,
                'comment' => 'Some Comment',
                'published' => 'N',
            ],
        ]));

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED)
        );

        $article = $Articles->get($article->id);
        $this->assertTrue($Articles->delete($article));
        $this->assertEmpty($article->getErrors());
    }

    /**
     * Tests that using associations with conditions that are referencing the main table works.
     */
    public function testConditionsReferencingParentColumnWithMustNotBeLinkedIsLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasOne('Comments', [
            'conditions' => function (QueryExpression $exp) {
                return $exp->eq(
                    new IdentifierExpression('Comments.published'),
                    new IdentifierExpression('Articles.published')
                );
            },
        ]);

        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Comments', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'comment',
            ]
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comment' => [
                '_isNotLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that using associations with custom finders works.
     */
    public function testFinderWithMustNotBeLinkedIsNotLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles', [
            'finder' => 'published',
        ]);

        $comment = $Comments->save($Comments->newEntity([
            'user_id' => 1,
            'comment' => 'Some Comment',
            'published' => 'Y',
            'article' => [
                'user_id' => 1,
                'body' => 'Some Text',
                'published' => 'N',
            ],
        ]));

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::STATUS_NOT_LINKED)
        );

        $comment = $Comments->get($comment->id);
        $this->assertTrue($Comments->delete($comment));
        $this->assertEmpty($comment->getErrors());
    }

    /**
     * Tests that using associations with custom finders works.
     */
    public function testFinderWithMustNotBeLinkedIsLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles', [
            'finder' => 'published',
        ]);

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::STATUS_NOT_LINKED),
            '_isLinkedTo',
            [
                'errorField' => 'article',
            ]
        );

        $comment = $Comments->get(1);
        $this->assertFalse($Comments->delete($comment));

        $expected = [
            'article' => [
                '_isLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests that using association instances works.
     */
    public function testAssociationInstanceWithMustBeLinkedIsLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint($Comments->getAssociation('Articles'), LinkConstraint::STATUS_LINKED)
        );

        $comment = $Comments->get(1);
        $comment->setDirty('comment', true);
        $this->assertNotFalse($Comments->save($comment));
        $this->assertEmpty($comment->getErrors());
    }

    /**
     * Tests that using association instances works.
     */
    public function testAssociationInstanceWithMustBeLinkedIsNotLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            new LinkConstraint($Comments->getAssociation('Articles'), LinkConstraint::STATUS_LINKED),
            '_isLinkedTo',
            [
                'errorField' => 'article',
            ]
        );

        $comment = $Comments->get(7);
        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'article' => [
                '_isLinkedTo' => 'invalid',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests implicit delete operations on `hasMany` associations.
     */
    public function testImplicitHasManyDeleteErrors(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles
            ->hasMany('Comments')
            ->setDependent(true)
            ->setCascadeCallbacks(true)
            ->setSaveStrategy(HasMany::SAVE_REPLACE);
        $Articles
            ->getAssociation('Comments')
            ->hasMany('Attachments');

        $rulesChecker = $Articles->getAssociation('Comments')->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Attachments', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'attachments',
            ]
        );

        $article = $Articles->get(2);
        $article->set('comments', [
            $Articles->getAssociation('Comments')->newEntity([
                'user_id' => 1,
                'comment' => 'New Comment',
            ]),
        ]);
        $article->setDirty('comments', true);
        $this->assertFalse($Articles->save($article));
        $this->assertEmpty(
            $article->getErrors(),
            'This should not be empty, but currently is because unlink errors are not being returned.'
        );

        $this->markTestIncomplete('This test is incomplete because currently unlink errors are not being returned.');
    }

    /**
     * Tests implicit delete operations on `belongsToMany` junction associations.
     */
    public function testImplicitBelongsToManyJunctionDeleteErrors(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles
            ->getAssociation('Tags')
            ->junction()
            ->belongsTo('Articles');

        $rulesChecker = $Articles->getAssociation('Tags')->junction()->rulesChecker();
        $rulesChecker->addDelete(
            new LinkConstraint('Articles', LinkConstraint::STATUS_NOT_LINKED),
            '_isNotLinkedTo',
            [
                'errorField' => 'articles',
            ]
        );

        $article = $Articles->get(1);
        $article->set('tags', [
            $Articles->getAssociation('Tags')->newEntity([
                'name' => 'New Tag',
                'description' => 'New Tag',
            ]),
        ]);
        $article->setDirty('tags', true);
        $this->assertFalse($Articles->save($article));
        $this->assertEmpty(
            $article->getErrors(),
            'This should not be empty, but currently is because junction delete errors are not being returned.'
        );

        $this->markTestIncomplete(
            'This test is incomplete because currently junction delete errors are not returned.'
        );
    }
}
