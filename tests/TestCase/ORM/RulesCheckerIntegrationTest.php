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
namespace Cake\Test\TestCase\ORM;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
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
    public $fixtures = [
        'core.articles', 'core.articles_tags', 'core.authors', 'core.tags',
        'core.special_tags', 'core.categories', 'core.site_articles', 'core.site_authors'
    ];

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

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
            'body' => 'A body'
        ]);
        $entity->author = new Entity([
            'name' => 'Jose'
        ]);

        $table = TableRegistry::get('articles');
        $table->belongsTo('authors');
        $table->association('authors')
            ->target()
            ->rulesChecker()
            ->add(
                function (Entity $author, array $options) use ($table) {
                    $this->assertSame($options['repository'], $table->association('authors')->target());

                    return false;
                },
                ['errorField' => 'name', 'message' => 'This is an error']
            );

        $this->assertFalse($table->save($entity));
        $this->assertTrue($entity->isNew());
        $this->assertTrue($entity->author->isNew());
        $this->assertNull($entity->get('author_id'));
        $this->assertNotEmpty($entity->author->errors('name'));
        $this->assertEquals(['This is an error'], $entity->author->errors('name'));
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
            'name' => 'Jose'
        ]);
        $entity->article = new Entity([
            'title' => 'A Title',
            'body' => 'A body'
        ]);

        $table = TableRegistry::get('authors');
        $table->hasOne('articles');
        $table->association('articles')
            ->target()
            ->rulesChecker()
            ->add(
                function (Entity $entity) {
                    return false;
                },
                ['errorField' => 'title', 'message' => 'This is an error']
            );

        $this->assertFalse($table->save($entity));
        $this->assertTrue($entity->isNew());
        $this->assertTrue($entity->article->isNew());
        $this->assertNull($entity->article->id);
        $this->assertNull($entity->article->get('author_id'));
        $this->assertFalse($entity->article->dirty('author_id'));
        $this->assertNotEmpty($entity->article->errors('title'));
        $this->assertSame('A Title', $entity->article->invalid('title'));
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
            'name' => 'Jose'
        ]);
        $entity->articles = [
            new Entity([
                'title' => '1',
                'body' => 'A body'
            ]),
            new Entity([
                'title' => 'Another Title',
                'body' => 'Another body'
            ])
        ];

        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $table->association('articles')
            ->target()
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
        $this->assertEmpty($entity->articles[0]->errors());
        $this->assertNotEmpty($entity->articles[1]->errors());
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
            'name' => 'Jose'
        ]);
        $entity->articles = [
            new Entity([
                'title' => 'A title',
                'body' => 'A body'
            ]),
            new Entity([
                'title' => '1',
                'body' => 'Another body'
            ])
        ];

        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $table->association('articles')
            ->target()
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
        $this->assertEquals(4, $entity->articles[1]->id);
        $this->assertNull($entity->articles[0]->id);
        $this->assertNotEmpty($entity->articles[0]->errors('title'));
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
            'body' => 'A body'
        ]);
        $entity->tags = [
            new Entity([
                'name' => 'Something New'
            ]),
            new Entity([
                'name' => '100'
            ])
        ];
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $table->association('tags')
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
            'body' => 'A body'
        ]);
        $entity->tags = [
            new Entity([
                'name' => 'Something New'
            ]),
            new Entity([
                'name' => 'New one'
            ])
        ];
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $table->association('tags')
            ->junction()
            ->rulesChecker()
            ->add(function (Entity $entity) {
                return $entity->tag_id > 4;
            });

        $this->assertSame($entity, $table->save($entity, ['atomic' => false]));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->tags[0]->isNew());
        $this->assertFalse($entity->tags[1]->isNew());
        $this->assertEquals(4, $entity->tags[0]->id);
        $this->assertEquals(5, $entity->tags[1]->id);
        $this->assertTrue($entity->tags[0]->_joinData->isNew());
        $this->assertEquals(4, $entity->tags[1]->_joinData->article_id);
        $this->assertEquals(5, $entity->tags[1]->_joinData->tag_id);
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
            'name' => 'larry'
        ]);

        $table = TableRegistry::get('Authors');
        $rules = $table->rulesChecker();
        $rules->add(
            function () {
                return false;
            },
            'ruleName',
            ['errorField' => 'name']
        );

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['ruleName' => 'invalid'], $entity->errors('name'));
    }

    /**
     * Ensure that add(isUnique()) only invokes a rule once.
     *
     * @return void
     */
    public function testIsUniqueRuleSingleInvocation()
    {
        $entity = new Entity([
            'name' => 'larry'
        ]);

        $table = TableRegistry::get('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['name']), '_isUnique', ['errorField' => 'title']);
        $this->assertFalse($table->save($entity));

        $this->assertEquals(
            ['_isUnique' => 'This value is already in use'],
            $entity->errors('title'),
            'Provided field should have errors'
        );
        $this->assertEmpty($entity->errors('name'), 'Errors should not apply to original field.');
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
            'name' => 'larry'
        ]);

        $table = TableRegistry::get('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['name']));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_isUnique' => 'This value is already in use'], $entity->errors('name'));

        $entity->name = 'jose';
        $this->assertSame($entity, $table->save($entity));

        $entity = $table->get(1);
        $entity->dirty('name', true);
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
            'title' => 'First Article'
        ]);

        $table = TableRegistry::get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['title', 'author_id'], 'Nope'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['title' => ['_isUnique' => 'Nope']], $entity->errors());

        $entity->clean();
        $entity->author_id = 2;
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests isUnique with allowMultipleNulls
     *
     * @group save
     * @return void
     */
    public function testIsUniqueAllowMultipleNulls()
    {
        $entity = new Entity([
            'article_id' => 11,
            'tag_id' => 11,
            'author_id' => null
        ]);

        $table = TableRegistry::get('SpecialTags');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['author_id'], [
            'allowMultipleNulls' => false,
            'message' => 'All fields are required'
        ]));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_isUnique' => 'All fields are required'], $entity->errors('author_id'));

        $entity->author_id = 11;
        $this->assertSame($entity, $table->save($entity));

        $entity = $table->get(1);
        $entity->dirty('author_id', true);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests isUnique with multiple fields and allowMultipleNulls
     *
     * @group save
     * @return void
     */
    public function testIsUniqueMultipleFieldsAllowMultipleNulls()
    {
        $entity = new Entity([
            'article_id' => 10,
            'tag_id' => 12,
            'author_id' => null
        ]);

        $table = TableRegistry::get('SpecialTags');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['author_id', 'article_id'], [
            'allowMultipleNulls' => false,
            'message' => 'Nope'
        ]));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_isUnique' => 'Nope']], $entity->errors());

        $entity->clean();
        $entity->article_id = 10;
        $entity->tag_id = 12;
        $entity->author_id = 12;
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests isUnique with multiple fields emulates SQL UNIQUE keys
     *
     * @group save
     * @return void
     */
    public function testIsUniqueMultipleFieldsOneIsNull()
    {
        $entity = new Entity([
            'author_id' => null,
            'title' => 'First Article'
        ]);
        $table = TableRegistry::get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['title', 'author_id'], 'Nope'));

        $this->assertSame($entity, $table->save($entity));

        // Make a matching record
        $entity = new Entity([
            'author_id' => null,
            'title' => 'New Article'
        ]);
        $this->assertSame($entity, $table->save($entity));
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
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'This value does not exist'], $entity->errors('author_id'));
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

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'), '_existsIn', ['errorField' => 'other']);
        $this->assertFalse($table->save($entity));

        $this->assertEquals(
            ['_existsIn' => 'This value does not exist'],
            $entity->errors('other'),
            'Provided field should have errors'
        );
        $this->assertEmpty($entity->errors('author_id'), 'Errors should not apply to original field.');
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
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', TableRegistry::get('Authors'), 'Nope'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'Nope'], $entity->errors('author_id'));
    }

    /**
     * ExistsIn uses the schema to verify that nullable fields are ok.
     *
     * @return
     */
    public function testExistsInNullValue()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => null
        ]);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $this->assertEquals($entity, $table->save($entity));
        $this->assertEquals([], $entity->errors('author_id'));
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
        $table = TableRegistry::get('Categories');
        $table->belongsTo('Categories', [
            'foreignKey' => 'parent_id',
            'bindingKey' => 'id',
        ]);
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('parent_id', 'Categories'));
        $this->assertTrue($table->checkRules($entity, RulesChecker::CREATE));
        $this->assertEmpty($entity->errors('parent_id'));
    }

    /**
     * Tests exists in uses the bindingKey of the association
     *
     * @return
     */
    public function testExistsInWithBindingKey()
    {
        $entity = new Entity([
            'title' => 'An Article',
        ]);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors', [
            'bindingKey' => 'name',
            'foreignKey' => 'title'
        ]);
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('title', 'Authors'));

        $this->assertFalse($table->save($entity));
        $this->assertNotEmpty($entity->errors('title'));

        $entity->clean();
        $entity->title = 'larry';
        $this->assertEquals($entity, $table->save($entity));
    }

    /**
     * Tests existsIn with invalid associations
     *
     * @group save
     * @expectedException \RuntimeException
     * @expectedExceptionMessage ExistsIn rule for 'author_id' is invalid. 'NotValid' is not associated with 'Cake\ORM\Table'.
     * @return void
     */
    public function testExistsInInvalidAssociation()
    {
        $entity = new Entity([
            'title' => 'An Article',
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'NotValid'));

        $table->save($entity);
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
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', TableRegistry::get('Authors'), 'Nope'));

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
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', TableRegistry::get('Authors'), 'Nope'));

        $table->eventManager()->on(
            'Model.beforeRules',
            function (Event $event, Entity $entity, \ArrayObject $options, $operation) {
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
                $this->assertEquals('create', $operation);
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
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', TableRegistry::get('Authors'), 'Nope'));

        $table->eventManager()->on(
            'Model.afterRules',
            function (Event $event, Entity $entity, \ArrayObject $options, $result, $operation) {
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
                $this->assertEquals('create', $operation);
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
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $table->eventManager()->on('Model.buildRules', function (Event $event, RulesChecker $rules) {
            $rules->add($rules->existsIn('author_id', TableRegistry::get('Authors'), 'Nope'));
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
        $table = TableRegistry::get('Articles');
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
            'author_id' => 1
        ]);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->isUnique(['author_id']));

        $table->Authors->eventManager()->on('Model.beforeFind', function (Event $event, $query) {
            $query->leftJoin(['a2' => 'authors']);
        });

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_isUnique' => 'This value is already in use'], $entity->errors('author_id'));
    }

    /**
     * Tests the existsIn rule when passing non dirty fields
     *
     * @group save
     * @return void
     */
    public function testExistsInWithCleanFields()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $entity = $table->get(1);
        $entity->title = 'Foo';
        $entity->author_id = 1000;
        $entity->dirty('author_id', false);
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
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $table->Authors->eventManager()->on('Model.beforeFind', function (Event $event, $query) {
            $query->leftJoin(['a2' => 'authors']);
        });

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'This value does not exist'], $entity->errors('author_id'));
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
            'author_id' => 500
        ]);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn(['author_id'], 'Authors'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'This value does not exist'], $entity->errors('author_id'));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorIdNullA()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true
        ]));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorIdNullB()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => false
        ]));
        $this->assertFalse($table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorIdNullC()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors'));
        $this->assertFalse($table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorIdNullD()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => false,
            'message' => 'Niente'
        ]));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'Niente']], $entity->errors());
    }

    /**
     * Tests new allowNullableNulls flag with author id set to null
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorIdNullE()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'Niente'
        ]));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorId1A()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', ['allowNullableNulls' => true]));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorIdB()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', ['allowNullableNulls' => false]));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorId1C()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors'));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorId1E()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will not error']));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorId1F()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => false,
            'message' => 'will not error']));
        $this->assertInstanceOf('Cake\ORM\Entity', $table->save($entity));
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 99999999 (does not exist)
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorId1G()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 99999999,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will error']));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'will error']], $entity->errors());
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 99999999 (does not exist)
     * and site_id set to 99999999 (does not exist)
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorId1H()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 99999999,
            'site_id' => 99999999,
            'name' => 'New Site Article with Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will error']));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'will error']], $entity->errors());
    }

    /**
     * Tests new allowNullableNulls flag with author id set to 1 (does exist)
     * and site_id set to 99999999 (does not exist)
     *
     * @return
     */
    public function testExistsInAllowNullableNullsWithAuthorId1I()
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 99999999,
            'name' => 'New Site Article with Author',
        ]);
        $table = TableRegistry::get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add($rules->existsIn(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => true,
            'message' => 'will error']));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'will error']], $entity->errors());
    }

    /**
     * Tests using rules to prevent delete operations
     *
     * @group delete
     * @return void
     */
    public function testDeleteRules()
    {
        $table = TableRegistry::get('Articles');
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
            'name' => 'jose'
        ]);

        $table = TableRegistry::get('Authors');
        $rules = $table->rulesChecker();
        $rules->add(function ($entity, $options) {
            $this->assertEquals('bar', $options['foo']);
            $this->assertEquals('option', $options['another']);

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
        $table = TableRegistry::get('Articles');
        $rules = $table->rulesChecker();
        $rules->addDelete(function ($entity, $options) {
            $this->assertEquals('bar', $options['foo']);
            $this->assertEquals('option', $options['another']);

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
            'name' => 'larry'
        ]);

        $table = TableRegistry::get('Authors');
        $rules = $table->rulesChecker();
        $rules->add(function () {
            return 'So much nope';
        }, ['errorField' => 'name']);

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['So much nope'], $entity->errors('name'));
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
            'name' => 'larry'
        ]);

        $table = TableRegistry::get('Authors');
        $rules = $table->rulesChecker();
        $rules->add(function () {
            return 'So much nope';
        });

        $this->assertFalse($table->save($entity));
        $this->assertEmpty($entity->errors());
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
            'name' => 'Jose'
        ]);
        $entity->articles = [
            new Entity([
                'title' => '1',
                'body' => 'A body'
            ]),
            new Entity([
                'title' => 'Another Title',
                'body' => 'Another body'
            ])
        ];

        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $table->association('articles')->belongsTo('authors');
        $checker = $table->association('articles')->target()->rulesChecker();
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
            'author_id' => 1
        ]);

        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors', [
            'conditions' => ['Authors.name !=' => 'mariano']
        ]);
        $rules = $table->rulesChecker();
        $rules->add($rules->existsIn('author_id', 'Authors'));

        $this->assertFalse($table->save($entity));
        $this->assertEquals(['_existsIn' => 'This value does not exist'], $entity->errors('author_id'));
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
            'body' => 'A body'
        ]);
        $entity->tags = [
            new Entity([
                'name' => 'Something New'
            ]),
            new Entity([
                'name' => '100'
            ])
        ];

        TableRegistry::get('ArticlesTags');

        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');

        $rules = $table->rulesChecker();
        $rules->add($rules->validCount('tags', 3));

        $this->assertFalse($table->save($entity));
        $this->assertEquals($entity->errors(), [
            'tags' => [
                '_validCount' => 'The count does not match >3'
            ]
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
}
