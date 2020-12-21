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
namespace Cake\Test\TestCase\ORM;

use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\I18n;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * Tests the integration between the ORM and the domain checker
 */
class RulesCheckerIntegrationTest extends TestCase
{
    /**
     * Fixtures to be loaded
     *
     * @var array
     */
    protected $fixtures = [
        'core.Articles', 'core.ArticlesTags', 'core.Authors', 'core.Comments', 'core.Tags',
        'core.SpecialTags', 'core.Categories', 'core.SiteArticles', 'core.SiteAuthors',
        'core.Comments', 'core.UniqueAuthors',
    ];

    /**
     * Tests saving belongsTo association and get a validation error
     *
     * @group save
     * @return void
     */
    public function testsSaveBelongsToWithValidationError()
    {
        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);
        $entity->author = new Entity([
            'name' => 'Jose',
        ]);

        $table = $this->getTableLocator()->get('articles');
        $table->belongsTo('authors');
        $table->getAssociation('authors')
            ->getTarget()
            ->rulesChecker()
            ->add(
                function (Entity $author, array $options) use ($table) {
                    $this->assertSame($options['repository'], $table->getAssociation('authors')->getTarget());

                    return false;
                },
                ['errorField' => 'name', 'message' => 'This is an error']
            );

        $this->assertFalse($table->save($entity));
        $this->assertTrue($entity->isNew());
        $this->assertTrue($entity->author->isNew());
        $this->assertNull($entity->get('author_id'));
        $this->assertNotEmpty($entity->author->getError('name'));
        $this->assertEquals(['This is an error'], $entity->author->getError('name'));
    }

    /**
     * Tests saving hasOne association and returning a validation error will
     * abort the saving process
     *
     * @group save
     * @return void
     */
    public function testSaveHasOneWithValidationError()
    {
        $entity = new Entity([
            'name' => 'Jose',
        ]);
        $entity->article = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);

        $table = $this->getTableLocator()->get('authors');
        $table->hasOne('articles');
        $table->getAssociation('articles')
            ->getTarget()
            ->rulesChecker()
            ->add(
                function (EntityInterface $entity) {
                    return false;
                },
                ['errorField' => 'title', 'message' => 'This is an error']
            );

        $this->assertFalse($table->save($entity));
        $this->assertTrue($entity->isNew());
        $this->assertTrue($entity->article->isNew());
        $this->assertNull($entity->article->id);
        $this->assertNull($entity->article->get('author_id'));
        $this->assertFalse($entity->article->isDirty('author_id'));
        $this->assertNotEmpty($entity->article->getError('title'));
        $this->assertSame('A Title', $entity->article->getInvalidField('title'));
    }

    /**
     * Tests saving multiple entities in a hasMany association and getting and
     * error while saving one of them. It should abort all the save operation
     * when options are set to defaults
     *
     * @return void
     */
    public function testSaveHasManyWithErrorsAtomic()
    {
        $entity = new Entity([
            'name' => 'Jose',
        ]);
        $entity->articles = [
            new Entity([
                'title' => '1',
                'body' => 'A body',
            ]),
            new Entity([
                'title' => 'Another Title',
                'body' => 'Another body',
            ]),
        ];

        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $table->getAssociation('articles')
            ->getTarget()
            ->rulesChecker()
            ->add(
                function (Entity $entity, $options) use ($table) {
                    $this->assertSame($table, $options['_sourceTable']);

                    return $entity->title === '1';
                },
                ['errorField' => 'title', 'message' => 'This is an error']
            );

        $this->assertFalse($table->save($entity));
        $this->assertTrue($entity->isNew());
        $this->assertTrue($entity->articles[0]->isNew());
        $this->assertTrue($entity->articles[1]->isNew());
        $this->assertNull($entity->articles[0]->id);
        $this->assertNull($entity->articles[1]->id);
        $this->assertNull($entity->articles[0]->author_id);
        $this->assertNull($entity->articles[1]->author_id);
        $this->assertEmpty($entity->articles[0]->getErrors());
        $this->assertNotEmpty($entity->articles[1]->getErrors());
    }

    /**
     * Tests that it is possible to continue saving hasMany associations
     * even if any of the records fail validation when atomic is set
     * to false
     *
     * @return void
     */
    public function testSaveHasManyWithErrorsNonAtomic()
    {
        $entity = new Entity([
            'name' => 'Jose',
        ]);
        $entity->articles = [
            new Entity([
                'title' => 'A title',
                'body' => 'A body',
            ]),
            new Entity([
                'title' => '1',
                'body' => 'Another body',
            ]),
        ];

        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $table->getAssociation('articles')
            ->getTarget()
            ->rulesChecker()
            ->add(
                function (Entity $article) {
                    return is_numeric($article->title);
                },
                ['errorField' => 'title', 'message' => 'This is an error']
            );

        $result = $table->save($entity, ['atomic' => false]);
        $this->assertSame($entity, $result);
        $this->assertFalse($entity->isNew());
        $this->assertTrue($entity->articles[0]->isNew());
        $this->assertFalse($entity->articles[1]->isNew());
        $this->assertSame(4, $entity->articles[1]->id);
        $this->assertNull($entity->articles[0]->id);
        $this->assertNotEmpty($entity->articles[0]->getError('title'));
    }

    /**
     * Tests saving belongsToMany records with a validation error in a joint entity
     *
     * @group save
     * @return void
     */
    public function testSaveBelongsToManyWithValidationErrorInJointEntity()
    {
        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);
        $entity->tags = [
            new Entity([
                'name' => 'Something New',
            ]),
            new Entity([
                'name' => '100',
            ]),
        ];
        $table = $this->getTableLocator()->get('articles');
        $table->belongsToMany('tags');
        $table->getAssociation('tags')
            ->junction()
            ->rulesChecker()
            ->add(function (Entity $entity) {
                return $entity->article_id > 4;
            });

        $this->assertFalse($table->save($entity));
        $this->assertTrue($entity->isNew());
        $this->assertTrue($entity->tags[0]->isNew());
        $this->assertTrue($entity->tags[1]->isNew());
        $this->assertNull($entity->tags[0]->id);
        $this->assertNull($entity->tags[1]->id);
        $this->assertNull($entity->tags[0]->_joinData);
        $this->assertNull($entity->tags[1]->_joinData);
    }

    /**
     * Tests saving belongsToMany records with a validation error in a joint entity
     * and atomic set to false
     *
     * @group save
     * @return void
     */
    public function testSaveBelongsToManyWithValidationErrorInJointEntityNonAtomic()
    {
        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);
        $entity->tags = [
            new Entity([
                'name' => 'Something New',
            ]),
            new Entity([
                'name' => 'New one',
            ]),
        ];
        $table = $this->getTableLocator()->get('articles');
        $table->belongsToMany('tags');
        $table->getAssociation('tags')
            ->junction()
            ->rulesChecker()
            ->add(function (Entity $entity) {
                return $entity->tag_id > 4;
            });

        $this->assertSame($entity, $table->save($entity, ['atomic' => false]));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->tags[0]->isNew());
        $this->assertFalse($entity->tags[1]->isNew());
        $this->assertSame(4, $entity->tags[0]->id);
        $this->assertSame(5, $entity->tags[1]->id);
        $this->assertTrue($entity->tags[0]->_joinData->isNew());
        $this->assertSame(4, $entity->tags[1]->_joinData->article_id);
        $this->assertSame(5, $entity->tags[1]->_joinData->tag_id);
    }

    /**
     * Test adding rule with name
     *
     * @group save
     * @return void
     */
    public function testAddingRuleWithName()
    {
        $entity = new Entity([
            'name' => 'larry',
        ]);

        $table = $this->getTableLocator()->get('Authors');
        $rules = $table->rulesChecker();
        $rules->add(
            function () {
                return false;
            },
            'ruleName',
            ['errorField' => 'name']
        );

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['ruleName' => 'invalid'], $entity->getError('name'));
    }

    /**
     * Ensure that add(isUnique()) only invokes a rule once.
     *
     * @return void
     */
    public function testIsUniqueRuleSingleInvocation()
    {
        $entity = new Entity([
            'name' => 'larry',
        ]);

        $table = $this->getTableLocator()->get('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['name']), '_isUnique', ['errorField' => 'title']);
        $this->assertFalse($table->save($entity));

        $this->assertEquals(
            ['_isUnique' => 'This value is already in use'],
            $entity->getError('title'),
            'Provided field should have errors'
        );
        $this->assertEmpty($entity->getError('name'), 'Errors should not apply to original field.');
    }

    /**
     * Tests the isUnique domain rule
     *
     * @group save
     * @return void
     */
    public function testIsUniqueDomainRule()
    {
        $entity = new Entity([
            'name' => 'larry',
        ]);

        $table = $this->getTableLocator()->get('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['name']));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_isUnique' => 'This value is already in use'], $entity->getError('name'));

        $entity->name = 'jose';
        $this->assertSame($entity, $table->save($entity));

        $entity = $table->get(1);
        $entity->setDirty('name', true);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests isUnique with multiple fields
     *
     * @group save
     * @return void
     */
    public function testIsUniqueMultipleFields()
    {
        $entity = new Entity([
            'author_id' => 1,
            'title' => 'First Article',
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['title', 'author_id'], 'Nope'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['title' => ['_isUnique' => 'Nope']], $entity->getErrors());

        $entity->clean();
        $entity->author_id = 2;
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests isUnique with non-unique null values
     *
     * @return void
     */
    public function testIsUniqueNonUniqueNulls()
    {
        $table = $this->getTableLocator()->get('UniqueAuthors');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(
            ['first_author_id', 'second_author_id']
        ));

        $entity = new Entity([
            'first_author_id' => null,
            'second_author_id' => 1,
        ]);
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['first_author_id' => ['_isUnique' => 'This value is already in use']], $entity->getErrors());
    }

    /**
     * Tests isUnique with allowMultipleNulls
     *
     * @group save
     * @return void
     */
    public function testIsUniqueAllowMultipleNulls()
    {
        $this->skipIf(ConnectionManager::get('test')->getDriver() instanceof Sqlserver);

        $table = $this->getTableLocator()->get('UniqueAuthors');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(
            ['first_author_id', 'second_author_id'],
            ['allowMultipleNulls' => true]
        ));

        $entity = new Entity([
            'first_author_id' => null,
            'second_author_id' => 1,
        ]);
        $this->assertNotEmpty($table->save($entity));

        $entity->first_author_id = 2;
        $this->assertSame($entity, $table->save($entity));

        $entity = new Entity([
            'first_author_id' => 2,
            'second_author_id' => 1,
        ]);
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['first_author_id' => ['_isUnique' => 'This value is already in use']], $entity->getErrors());
    }

    /**
     * Tests the existsIn domain rule
     *
     * @group save
     * @return void
     */
    public function testExistsInDomainRule()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'This value does not exist'], $entity->getError('author_id'));
    }

    /**
     * Ensure that add(existsIn()) only invokes a rule once.
     *
     * @return void
     */
    public function testExistsInRuleSingleInvocation()
    {
        $entity = new Entity([
            'title' => 'larry',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'), '_existsIn', ['errorField' => 'other']);
        $this->assertFalse($table->save($entity));

        $this->assertEquals(
            ['_existsIn' => 'This value does not exist'],
            $entity->getError('other'),
            'Provided field should have errors'
        );
        $this->assertEmpty($entity->getError('author_id'), 'Errors should not apply to original field.');
    }

    /**
     * Tests the existsIn domain rule when passing an object
     *
     * @group save
     * @return void
     */
    public function testExistsInDomainRuleWithObject()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', $this->getTableLocator()->get('Authors'), 'Nope'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'Nope'], $entity->getError('author_id'));
    }

    /**
     * ExistsIn uses the schema to verify that nullable fields are ok.
     *
     * @return void
     */
    public function testExistsInNullValue()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => null,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $this->assertEquals($entity, $table->save($entity));
        $this->assertEquals([], $entity->getError('author_id'));
    }

    /**
     * Test ExistsIn on a new entity that doesn't have the field populated.
     *
     * This use case is important for saving records and their
     * associated belongsTo records in one pass.
     *
     * @return void
     */
    public function testExistsInNotNullValueNewEntity()
    {
        $entity = new Entity([
            'name' => 'A Category',
        ]);
        $table = $this->getTableLocator()->get('Categories');
        $table->belongsTo('Categories', [
            'foreignKey' => 'parent_id',
            'bindingKey' => 'id',
        ]);
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('parent_id', 'Categories'));
        $this->assertTrue($table->checkRules($entity, RulesChecker::CREATE));
        $this->assertEmpty($entity->getError('parent_id'));
    }

    /**
     * Tests exists in uses the bindingKey of the association
     *
     * @return void
     */
    public function testExistsInWithBindingKey()
    {
        $entity = new Entity([
            'title' => 'An Article',
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors', [
            'bindingKey' => 'name',
            'foreignKey' => 'title',
        ]);
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('title', 'Authors'));

        $this->assertFalse($table->save($entity));
        $this->assertNotEmpty($entity->getError('title'));

        $entity->clean();
        $entity->title = 'larry';
        $this->assertEquals($entity, $table->save($entity));
    }

    /**
     * Tests existsIn with invalid associations
     *
     * @group save
     * @return void
     */
    public function testExistsInInvalidAssociation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ExistsIn rule for \'author_id\' is invalid. \'NotValid\' is not associated with \'Cake\ORM\Table\'.');
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'NotValid'));

        $table->save($entity);
    }

    /**
     * Tests existsIn does not prevent new entities from saving if parent entity is new
     *
     * @return void
     */
    public function testExistsInHasManyNewEntities()
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->Comments->belongsTo('Articles');

        $rules = $table->Comments->rulesChecker();
        $rules->add($rules->existsIn(['article_id'], $table));

        $article = $table->newEntity([
            'title' => 'new article',
            'comments' => [
                $table->Comments->newEntity([
                    'user_id' => 1,
                    'comment' => 'comment 1',
                ]),
                $table->Comments->newEntity([
                    'user_id' => 1,
                    'comment' => 'comment 2',
                ]),
            ],
        ]);

        $this->assertNotFalse($table->save($article));
    }

    /**
     * Tests existsIn does not prevent new entities from saving if parent entity is new,
     * getting the parent entity from the association
     *
     * @return void
     */
    public function testExistsInHasManyNewEntitiesViaAssociation()
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->Comments->belongsTo('Articles');

        $rules = $table->Comments->rulesChecker();
        $rules->add($rules->existsIn(['article_id'], 'Articles'));

        $article = $table->newEntity([
            'title' => 'test',
        ]);

        $article->comments = [
            $table->Comments->newEntity([
                'user_id' => 1,
                'comment' => 'test',
            ]),
        ];

        $this->assertNotFalse($table->save($article));
    }

    /**
     * Tests the checkRules save option
     *
     * @group save
     * @return void
     */
    public function testSkipRulesChecking()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', $this->getTableLocator()->get('Authors'), 'Nope'));

        $this->assertSame($entity, $table->save($entity, ['checkRules' => false]));
    }

    /**
     * Tests the beforeRules event
     *
     * @group save
     * @return void
     */
    public function testUseBeforeRules()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', $this->getTableLocator()->get('Authors'), 'Nope'));

        $table->getEventManager()->on(
            'Model.beforeRules',
            function (EventInterface $event, EntityInterface $entity, \ArrayObject $options, $operation) {
                $this->assertEquals(
                    [
                        'atomic' => true,
                        'associated' => true,
                        'checkRules' => true,
                        'checkExisting' => true,
                        '_primary' => true,
                    ],
                    $options->getArrayCopy()
                );
                $this->assertSame('create', $operation);
                $event->stopPropagation();

                return true;
            }
        );

        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests the afterRules event
     *
     * @group save
     * @return void
     */
    public function testUseAfterRules()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', $this->getTableLocator()->get('Authors'), 'Nope'));

        $table->getEventManager()->on(
            'Model.afterRules',
            function (EventInterface $event, EntityInterface $entity, \ArrayObject $options, $result, $operation) {
                $this->assertEquals(
                    [
                        'atomic' => true,
                        'associated' => true,
                        'checkRules' => true,
                        'checkExisting' => true,
                        '_primary' => true,
                    ],
                    $options->getArrayCopy()
                );
                $this->assertSame('create', $operation);
                $this->assertFalse($result);
                $event->stopPropagation();

                return true;
            }
        );

        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that rules can be changed using the buildRules event
     *
     * @group save
     * @return void
     */
    public function testUseBuildRulesEvent()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->getEventManager()->on('Model.buildRules', function (EventInterface $event, RulesChecker $rules) {
            $rules->add($rules->existsIn('author_id', $this->getTableLocator()->get('Authors'), 'Nope'));
        });

        $this->assertFalse($table->save($entity));
    }

    /**
     * Tests isUnique with untouched fields
     *
     * @group save
     * @return void
     */
    public function testIsUniqueWithCleanFields()
    {
        $table = $this->getTableLocator()->get('Articles');
        $entity = $table->get(1);
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['title', 'author_id'], 'Nope'));

        $entity->body = 'Foo';
        $this->assertSame($entity, $table->save($entity));

        $entity->title = 'Third Article';
        $this->assertFalse($table->save($entity));
    }

    /**
     * Tests isUnique rule with conflicting columns
     *
     * @group save
     * @return void
     */
    public function testIsUniqueAliasPrefix()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 1,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['author_id']));

        $table->Authors->getEventManager()->on('Model.beforeFind', function (EventInterface $event, $query) {
            $query->leftJoin(['a2' => 'authors']);
        });

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_isUnique' => 'This value is already in use'], $entity->getError('author_id'));
    }

    /**
     * Tests the existsIn rule when passing non dirty fields
     *
     * @group save
     * @return void
     */
    public function testExistsInWithCleanFields()
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $entity = $table->get(1);
        $entity->title = 'Foo';
        $entity->author_id = 1000;
        $entity->setDirty('author_id', false);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests the existsIn with conflicting columns
     *
     * @group save
     * @return void
     */
    public function testExistsInAliasPrefix()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $table->Authors->getEventManager()->on('Model.beforeFind', function (EventInterface $event, $query) {
            $query->leftJoin(['a2' => 'authors']);
        });

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'This value does not exist'], $entity->getError('author_id'));
    }

    /**
     * Tests that using an array in existsIn() sets the error message correctly
     *
     * @return void
     */
    public function testExistsInErrorWithArrayField()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn(['author_id'], 'Authors'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'This value does not exist'], $entity->getError('author_id'));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsOn()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
        ]));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsOff()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => false,
        ]));
        $this->assertFalse($table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsDefaultValue()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors'));
        $this->assertFalse($table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsCustomMessage()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => false,
            'message' => 'Niente',
        ]));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'Niente']], $entity->getErrors());
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsOnAllKeysSet()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', ['allowNullableNulls' => true]));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsOffAllKeysSet()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', ['allowNullableNulls' => false]));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsOnAllKeysCustomMessage()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will not error']));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 99999999 (does not exist)
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsOnInvalidKey()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 99999999,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will error']));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'will error']], $entity->getErrors());
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 99999999 (does not exist)
     * and site_id set to 99999999 (does not exist)
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsOnInvalidKeys()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 99999999,
            'site_id' => 99999999,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will error']));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'will error']], $entity->getErrors());
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1 (does exist)
     * and site_id set to 99999999 (does not exist)
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsOnInvalidKeySecond()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 99999999,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will error']));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'will error']], $entity->getErrors());
    }

    /**
     * Tests new allowNullableNulls with saveMany
     *
     * @return void
     */
    public function testExistsInAllowNullableNullsSaveMany()
    {
        $entities = [
            new Entity([
                'id' => 1,
                'author_id' => null,
                'site_id' => 1,
                'name' => 'New Site Article without Author',
            ]),
            new Entity([
                'id' => 2,
                'author_id' => 1,
                'site_id' => 1,
                'name' => 'New Site Article with Author',
            ]),
        ];
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will error with array_combine warning']));
        $result = $table->saveMany($entities);
        $this->assertCount(2, $result);

        $this->assertInstanceOf(Entity::class, $result[0]);
        $this->assertEmpty($result[0]->getErrors());

        $this->assertInstanceOf(Entity::class, $result[1]);
        $this->assertEmpty($result[1]->getErrors());
    }

    /**
     * Tests using rules to prevent delete operations
     *
     * @group delete
     * @return void
     */
    public function testDeleteRules()
    {
        $table = $this->getTableLocator()->get('Articles');
        $rules = $table->rulesChecker();
        $rules->addDelete(function ($entity) {
            return false;
        });

        $entity = $table->get(1);
        $this->assertFalse($table->delete($entity));
    }

    /**
     * Checks that it is possible to pass custom options to rules when saving
     *
     * @group save
     * @return void
     */
    public function testCustomOptionsPassingSave()
    {
        $entity = new Entity([
            'name' => 'jose',
        ]);

        $table = $this->getTableLocator()->get('Authors');
        $rules = $table->rulesChecker();
        $rules->add(function ($entity, $options) {
            $this->assertSame('bar', $options['foo']);
            $this->assertSame('option', $options['another']);

            return false;
        }, ['another' => 'option']);

        $this->assertFalse($table->save($entity, ['foo' => 'bar']));
    }

    /**
     * Tests passing custom options to rules from delete
     *
     * @group delete
     * @return void
     */
    public function testCustomOptionsPassingDelete()
    {
        $table = $this->getTableLocator()->get('Articles');
        $rules = $table->rulesChecker();
        $rules->addDelete(function ($entity, $options) {
            $this->assertSame('bar', $options['foo']);
            $this->assertSame('option', $options['another']);

            return false;
        }, ['another' => 'option']);

        $entity = $table->get(1);
        $this->assertFalse($table->delete($entity, ['foo' => 'bar']));
    }

    /**
     * Test adding rules that return error string
     *
     * @group save
     * @return void
     */
    public function testCustomErrorMessageFromRule()
    {
        $entity = new Entity([
            'name' => 'larry',
        ]);

        $table = $this->getTableLocator()->get('Authors');
        $rules = $table->rulesChecker();
        $rules->add(function () {
            return 'So much nope';
        }, ['errorField' => 'name']);

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['So much nope'], $entity->getError('name'));
    }

    /**
     * Test adding rules with no errorField do not accept strings
     *
     * @group save
     * @return void
     */
    public function testCustomErrorMessageFromRuleNoErrorField()
    {
        $entity = new Entity([
            'name' => 'larry',
        ]);

        $table = $this->getTableLocator()->get('Authors');
        $rules = $table->rulesChecker();
        $rules->add(function () {
            return 'So much nope';
        });

        $this->assertFalse($table->save($entity));
        $this->assertEmpty($entity->getErrors());
    }

    /**
     * Tests that using existsIn for a hasMany association will not be called
     * as the foreign key for the association was automatically validated already.
     *
     * @group save
     * @return void
     */
    public function testAvoidExistsInOnAutomaticSaving()
    {
        $entity = new Entity([
            'name' => 'Jose',
        ]);
        $entity->articles = [
            new Entity([
                'title' => '1',
                'body' => 'A body',
            ]),
            new Entity([
                'title' => 'Another Title',
                'body' => 'Another body',
            ]),
        ];

        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('articles');
        $table->getAssociation('articles')->belongsTo('authors');
        $checker = $table->getAssociation('articles')->getTarget()->rulesChecker();
        $checker->add(function ($entity, $options) use ($checker) {
            $rule = $checker->existsIn('author_id', 'authors');
            $id = $entity->author_id;
            $entity->author_id = 5000;
            $result = $rule($entity, $options);
            $this->assertTrue($result);
            $entity->author_id = $id;

            return true;
        });

        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests the existsIn domain rule respects the conditions set for the associations
     *
     * @group save
     * @return void
     */
    public function testExistsInDomainRuleWithAssociationConditions()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 1,
        ]);

        $table = $this->getTableLocator()->get('Articles');
        $table->belongsTo('Authors', [
            'conditions' => ['Authors.name !=' => 'mariano'],
        ]);
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'This value does not exist'], $entity->getError('author_id'));
    }

    /**
     * Tests that associated items have a count of X.
     *
     * @return void
     */
    public function testCountOfAssociatedItems()
    {
        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);
        $entity->tags = [
            new Entity([
                'name' => 'Something New',
            ]),
            new Entity([
                'name' => '100',
            ]),
        ];

        $this->getTableLocator()->get('ArticlesTags');

        $table = $this->getTableLocator()->get('articles');
        $table->belongsToMany('tags');

        $rules = $table->rulesChecker();
        $rules->add($rules->validCount('tags', 3));

        $this->assertFalse($table->save($entity));
        $this->assertEquals($entity->getErrors(), [
            'tags' => [
                '_validCount' => 'The count does not match >3',
            ],
        ]);

        // Testing that undesired types fail
        $entity->tags = null;
        $this->assertFalse($table->save($entity));

        $entity->tags = new \stdClass();
        $this->assertFalse($table->save($entity));

        $entity->tags = 'string';
        $this->assertFalse($table->save($entity));

        $entity->tags = 123456;
        $this->assertFalse($table->save($entity));

        $entity->tags = 0.512;
        $this->assertFalse($table->save($entity));
    }

    /**
     * Tests that an exception is thrown when passing an invalid value for the `$association` argument.
     *
     * @return void
     */
    public function testIsLinkedToInvalidArgumentOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument 1 is expected to be of type `\Cake\ORM\Association|string`, `NULL` given.');

        $Comments = $this->getTableLocator()->get('Comments');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->isLinkedTo(null);
    }

    /**
     * Tests that an exception is thrown when passing an invalid value for the `$association` argument.
     *
     * @return void
     */
    public function testIsNotLinkedToInvalidArgumentOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument 1 is expected to be of type `\Cake\ORM\Association|string`, `NULL` given.');

        $Comments = $this->getTableLocator()->get('Comments');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->isNotLinkedTo(null);
    }

    /**
     * Tests that the error field name is inferred from the association name in case no name is provided.
     *
     * @return void
     */
    public function testIsLinkedToInferFieldFromAssociationName(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $comment = $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            $rulesChecker->isLinkedTo('Articles')
        );

        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'article' => [
                '_isLinkedTo' => 'Cannot modify row: a constraint for the `Articles` association fails.',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests that the error field name is inferred from the association name in case no name is provided.
     *
     * @return void
     */
    public function testIsNotLinkedToInferFieldFromAssociationName(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            $rulesChecker->isNotLinkedTo('Comments')
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comments' => [
                '_isNotLinkedTo' => 'Cannot modify row: a constraint for the `Comments` association fails.',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that the error field name is inferred from the association name in case no name is provided,
     * and no repository is available at the time of creating the rule.
     *
     * @return void
     */
    public function testIsLinkedToInferFieldFromAssociationNameWithNoRepositoryAvailable(): void
    {
        $rulesChecker = new RulesChecker();

        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject $Comments */
        $Comments = $this->getMockForModel('Comments', ['rulesChecker'], ['className' => Table::class]);
        $Comments
            ->expects($this->any())
            ->method('rulesChecker')
            ->willReturn($rulesChecker);

        $Comments->belongsTo('Articles');

        $comment = $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        $rulesChecker->addUpdate(
            $rulesChecker->isLinkedTo('Articles'),
            ['repository' => $Comments]
        );

        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'articles' => [
                '_isLinkedTo' => 'Cannot modify row: a constraint for the `Articles` association fails.',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests that the error field name is inferred from the association name in case no name is provided,
     * and no repository is available at the time of creating the rule.
     *
     * @return void
     */
    public function testIsNotLinkedToInferFieldFromAssociationNameWithNoRepositoryAvailable(): void
    {
        $rulesChecker = new RulesChecker();

        /** @var \Cake\ORM\Table&\PHPUnit\Framework\MockObject\MockObject $Articles */
        $Articles = $this->getMockForModel('Articles', ['rulesChecker'], ['className' => Table::class]);
        $Articles
            ->expects($this->any())
            ->method('rulesChecker')
            ->willReturn($rulesChecker);

        $Articles->hasMany('Comments');

        $rulesChecker->addDelete(
            $rulesChecker->isNotLinkedTo('Comments'),
            ['repository' => $Articles]
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comments' => [
                '_isNotLinkedTo' => 'Cannot modify row: a constraint for the `Comments` association fails.',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that the error field name is inferred from the association object in case no name is provided.
     *
     * @return void
     */
    public function testIsLinkedToInferFieldFromAssociationObject(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $comment = $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            $rulesChecker->isLinkedTo($Comments->getAssociation('Articles'))
        );

        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'article' => [
                '_isLinkedTo' => 'Cannot modify row: a constraint for the `Articles` association fails.',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests that the error field name is inferred from the association object in case no name is provided.
     *
     * @return void
     */
    public function testIsNotLinkedToInferFieldFromAssociationObject(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            $rulesChecker->isNotLinkedTo($Articles->getAssociation('Comments'))
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comments' => [
                '_isNotLinkedTo' => 'Cannot modify row: a constraint for the `Comments` association fails.',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that the custom error field name is being used.
     *
     * @return void
     */
    public function testIsLinkedToWithCustomField(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $comment = $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            $rulesChecker->isLinkedTo('Articles', 'custom')
        );

        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'custom' => [
                '_isLinkedTo' => 'Cannot modify row: a constraint for the `Articles` association fails.',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests that the custom error field name is being used.
     *
     * @return void
     */
    public function testIsNotLinkedToWithCustomField(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            $rulesChecker->isNotLinkedTo('Comments', 'custom')
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'custom' => [
                '_isNotLinkedTo' => 'Cannot modify row: a constraint for the `Comments` association fails.',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that the custom error message is being used.
     *
     * @return void
     */
    public function testIsLinkedToWithCustomMessage(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $comment = $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            $rulesChecker->isLinkedTo('Articles', 'article', 'custom')
        );

        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'article' => [
                '_isLinkedTo' => 'custom',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());
    }

    /**
     * Tests that the custom error message is being used.
     *
     * @return void
     */
    public function testIsNotLinkedToWithCustomMessage(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            $rulesChecker->isNotLinkedTo('Comments', 'comments', 'custom')
        );

        $article = $Articles->get(1);
        $this->assertFalse($Articles->delete($article));

        $expected = [
            'comments' => [
                '_isNotLinkedTo' => 'custom',
            ],
        ];
        $this->assertEquals($expected, $article->getErrors());
    }

    /**
     * Tests that the default error message can be translated.
     *
     * @return void
     */
    public function testIsLinkedToMessageWithI18n(): void
    {
        /** @var \Cake\I18n\Translator $translator */
        $translator = I18n::getTranslator('cake');

        $messageId = 'Cannot modify row: a constraint for the `{0}` association fails.';
        $translator->getPackage()->addMessage(
            $messageId,
            'Zeile kann nicht geändert werden: Eine Einschränkung für die "{0}" Beziehung schlägt fehl.'
        );

        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $comment = $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();

        $rulesChecker->addUpdate(
            $rulesChecker->isLinkedTo('Articles', 'article')
        );

        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'article' => [
                '_isLinkedTo' => 'Zeile kann nicht geändert werden: Eine Einschränkung für die "Articles" Beziehung schlägt fehl.',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());

        $translator->getPackage()->addMessage($messageId, '');
    }

    /**
     * Tests that the default error message can be translated.
     *
     * @return void
     */
    public function testIsNotLinkedToMessageWithI18n(): void
    {
        /** @var \Cake\I18n\Translator $translator */
        $translator = I18n::getTranslator('cake');

        $messageId = 'Cannot modify row: a constraint for the `{0}` association fails.';
        $translator->getPackage()->addMessage(
            $messageId,
            'Zeile kann nicht geändert werden: Eine Einschränkung für die "{0}" Beziehung schlägt fehl.'
        );

        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();

        $rulesChecker->addUpdate(
            $rulesChecker->isNotLinkedTo('Articles', 'articles')
        );

        $comment = $Comments->get(1);
        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'articles' => [
                '_isNotLinkedTo' => 'Zeile kann nicht geändert werden: Eine Einschränkung für die "Articles" Beziehung schlägt fehl.',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());

        $translator->getPackage()->addMessage($messageId, '');
    }

    /**
     * Tests that the default error message works without I18n.
     *
     * @return void
     */
    public function testIsLinkedToMessageWithoutI18n(): void
    {
        /** @var \Cake\I18n\Translator $translator */
        $translator = I18n::getTranslator('cake');

        $messageId = 'Cannot modify row: a constraint for the `{0}` association fails.';
        $translator->getPackage()->addMessage(
            $messageId,
            'translated'
        );

        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        $comment = $Comments->save($Comments->newEntity([
            'article_id' => 9999,
            'user_id' => 1,
            'comment' => 'Orphaned Comment',
        ]));

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();

        \Closure::bind(
            function () use ($rulesChecker) {
                $rulesChecker->{'_useI18n'} = false;
            },
            null,
            RulesChecker::class
        )();

        $rulesChecker->addUpdate(
            $rulesChecker->isLinkedTo('Articles', 'article')
        );

        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'article' => [
                '_isLinkedTo' => 'Cannot modify row: a constraint for the `Articles` association fails.',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());

        $translator->getPackage()->addMessage($messageId, '');
    }

    /**
     * Tests that the default error message works without I18n.
     *
     * @return void
     */
    public function testIsNotLinkedToMessageWithoutI18n(): void
    {
        /** @var \Cake\I18n\Translator $translator */
        $translator = I18n::getTranslator('cake');

        $messageId = 'Cannot modify row: a constraint for the `{0}` association fails.';
        $translator->getPackage()->addMessage(
            $messageId,
            'translated'
        );

        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();

        \Closure::bind(
            function () use ($rulesChecker) {
                $rulesChecker->{'_useI18n'} = false;
            },
            null,
            RulesChecker::class
        )();

        $rulesChecker->addUpdate(
            $rulesChecker->isNotLinkedTo('Articles', 'articles')
        );

        $comment = $Comments->get(1);
        $comment->setDirty('comment', true);
        $this->assertFalse($Comments->save($comment));

        $expected = [
            'articles' => [
                '_isNotLinkedTo' => 'Cannot modify row: a constraint for the `Articles` association fails.',
            ],
        ];
        $this->assertEquals($expected, $comment->getErrors());

        $translator->getPackage()->addMessage($messageId, '');
    }

    /**
     * Tests that the rule can pass.
     *
     * @return void
     */
    public function testIsLinkedToIsLinked(): void
    {
        $Comments = $this->getTableLocator()->get('Comments');
        $Comments->belongsTo('Articles');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Comments->rulesChecker();
        $rulesChecker->addUpdate(
            $rulesChecker->isLinkedTo('Articles', 'articles')
        );

        $comment = $Comments->get(1);
        $comment->setDirty('comment', true);
        $this->assertNotFalse($Comments->save($comment));
    }

    /**
     * Tests that the rule can pass.
     *
     * @return void
     */
    public function testIsNotLinkedToIsNotLinked(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->hasMany('Comments');

        /** @var \Cake\ORM\RulesChecker $rulesChecker */
        $rulesChecker = $Articles->rulesChecker();
        $rulesChecker->addDelete(
            $rulesChecker->isNotLinkedTo('Comments', 'comments')
        );

        $article = $Articles->get(3);
        $this->assertTrue($Articles->delete($article));
    }
}
