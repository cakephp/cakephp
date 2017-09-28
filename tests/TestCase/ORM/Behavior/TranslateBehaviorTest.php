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
namespace Cake\Test\TestCase\ORM\Behavior;

use Cake\Collection\Collection;
use Cake\I18n\I18n;
use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;

/**
 * Stub entity class
 */
class Article extends Entity
{

    use TranslateTrait;
}

/**
 * Translate behavior test case
 */
class TranslateBehaviorTest extends TestCase
{

    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.articles',
        'core.authors',
        'core.groups',
        'core.special_tags',
        'core.tags',
        'core.comments',
        'core.translates'
    ];

    public function tearDown()
    {
        parent::tearDown();
        I18n::setLocale(I18n::defaultLocale());
        TableRegistry::clear();
    }

    /**
     * Returns an array with all the translations found for a set of records
     *
     * @param array|\Traversable $data
     * @return Collection
     */
    protected function _extractTranslations($data)
    {
        return (new Collection($data))->map(function ($row) {
            $translations = $row->get('_translations');
            if (!$translations) {
                return [];
            }

            return array_map(function ($t) {
                return $t->toArray();
            }, $translations);
        });
    }

    /**
     * Tests that custom translation tables are respected
     *
     * @return void
     */
    public function testCustomTranslationTable()
    {
        $table = TableRegistry::get('Articles');

        $table->addBehavior('Translate', [
            'translationTable' => '\TestApp\Model\Table\I18nTable',
            'fields' => ['title', 'body']
        ]);

        $items = $table->associations();
        $i18n = $items->getByProperty('_i18n');

        $this->assertEquals('\TestApp\Model\Table\I18nTable', $i18n->name());
        $this->assertInstanceOf('TestApp\Model\Table\I18nTable', $i18n->target());
        $this->assertEquals('test_custom_i18n_datasource', $i18n->target()->connection()->configName());
        $this->assertEquals('custom_i18n_table', $i18n->target()->table());
    }

    /**
     * Tests that the strategy can be changed for i18n
     *
     * @return void
     */
    public function testStrategy()
    {
        $table = TableRegistry::get('Articles');

        $table->addBehavior('Translate', [
            'strategy' => 'select',
            'fields' => ['title', 'body']
        ]);

        $items = $table->associations();
        $i18n = $items->getByProperty('_i18n');

        $this->assertEquals('select', $i18n->strategy());
    }

    /**
     * Tests that fields from a translated model are overridden
     *
     * @return void
     */
    public function testFindSingleLocale()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('eng');
        $results = $table->find()->combine('title', 'body', 'id')->toArray();
        $expected = [
            1 => ['Title #1' => 'Content #1'],
            2 => ['Title #2' => 'Content #2'],
            3 => ['Title #3' => 'Content #3'],
        ];
        $this->assertSame($expected, $results);
    }

    /**
     * Test that iterating in a formatResults() does not drop data.
     *
     * @return void
     */
    public function testFindTranslationsFormatResultsIteration()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('eng');
        $results = $table->find('translations')
            ->limit(1)
            ->formatResults(function ($results) {
                foreach ($results as $res) {
                    $res->first = 'val';
                }
                foreach ($results as $res) {
                    $res->second = 'loop';
                }

                return $results;
            })
            ->toArray();
        $this->assertCount(1, $results);
        $this->assertSame('Title #1', $results[0]->title);
        $this->assertSame('val', $results[0]->first);
        $this->assertSame('loop', $results[0]->second);
        $this->assertNotEmpty($results[0]->_translations);
    }

    /**
     * Tests that fields from a translated model use the I18n class locale
     * and that it propogates to associated models
     *
     * @return void
     */
    public function testFindSingleLocaleAssociatedEnv()
    {
        I18n::setLocale('eng');

        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $table->hasMany('Comments');
        $table->Comments->addBehavior('Translate', ['fields' => ['comment']]);

        $results = $table->find()
            ->select(['id', 'title', 'body'])
            ->contain(['Comments' => ['fields' => ['article_id', 'comment']]])
            ->hydrate(false)
            ->toArray();

        $expected = [
            [
                'id' => 1,
                'title' => 'Title #1',
                'body' => 'Content #1',
                'comments' => [
                    ['article_id' => 1, 'comment' => 'Comment #1', '_locale' => 'eng'],
                    ['article_id' => 1, 'comment' => 'Comment #2', '_locale' => 'eng'],
                    ['article_id' => 1, 'comment' => 'Comment #3', '_locale' => 'eng'],
                    ['article_id' => 1, 'comment' => 'Comment #4', '_locale' => 'eng']
                ],
                '_locale' => 'eng'
            ],
            [
                'id' => 2,
                'title' => 'Title #2',
                'body' => 'Content #2',
                'comments' => [
                    ['article_id' => 2, 'comment' => 'First Comment for Second Article', '_locale' => 'eng'],
                    ['article_id' => 2, 'comment' => 'Second Comment for Second Article', '_locale' => 'eng']
                ],
                '_locale' => 'eng'
            ],
            [
                'id' => 3,
                'title' => 'Title #3',
                'body' => 'Content #3',
                'comments' => [],
                '_locale' => 'eng'
            ]
        ];
        $this->assertSame($expected, $results);

        I18n::setLocale('spa');

        $results = $table->find()
            ->select(['id', 'title', 'body'])
            ->contain([
                'Comments' => [
                    'fields' => ['article_id', 'comment'],
                    'sort' => ['Comments.id' => 'ASC']
                ]
            ])
            ->hydrate(false)
            ->toArray();

        $expected = [
            [
                'id' => 1,
                'title' => 'First Article',
                'body' => 'Contenido #1',
                'comments' => [
                    ['article_id' => 1, 'comment' => 'First Comment for First Article', '_locale' => 'spa'],
                    ['article_id' => 1, 'comment' => 'Second Comment for First Article', '_locale' => 'spa'],
                    ['article_id' => 1, 'comment' => 'Third Comment for First Article', '_locale' => 'spa'],
                    ['article_id' => 1, 'comment' => 'Comentario #4', '_locale' => 'spa']
                ],
                '_locale' => 'spa'
            ],
            [
                'id' => 2,
                'title' => 'Second Article',
                'body' => 'Second Article Body',
                'comments' => [
                    ['article_id' => 2, 'comment' => 'First Comment for Second Article', '_locale' => 'spa'],
                    ['article_id' => 2, 'comment' => 'Second Comment for Second Article', '_locale' => 'spa']
                ],
                '_locale' => 'spa'
            ],
            [
                'id' => 3,
                'title' => 'Third Article',
                'body' => 'Third Article Body',
                'comments' => [],
                '_locale' => 'spa'
            ]
        ];
        $this->assertSame($expected, $results);
    }

    /**
     * Tests that fields from a translated model are not overridden if translation
     * is null
     *
     * @return void
     */
    public function testFindSingleLocaleWithNullTranslation()
    {
        $table = TableRegistry::get('Comments');
        $table->addBehavior('Translate', ['fields' => ['comment']]);
        $table->locale('spa');
        $results = $table->find()
            ->where(['Comments.id' => 6])
            ->combine('id', 'comment')->toArray();
        $expected = [6 => 'Second Comment for Second Article'];
        $this->assertSame($expected, $results);
    }

    /**
     * Tests that overriding fields with the translate behavior works when
     * using conditions and that all other columns are preserved
     *
     * @return void
     */
    public function testFindSingleLocaleWithConditions()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('eng');
        $results = $table->find()
            ->where(['Articles.id' => 2])
            ->all();

        $this->assertCount(1, $results);
        $row = $results->first();

        $expected = [
            'id' => 2,
            'title' => 'Title #2',
            'body' => 'Content #2',
            'author_id' => 3,
            'published' => 'Y',
            '_locale' => 'eng'
        ];
        $this->assertEquals($expected, $row->toArray());
    }

    /**
     * Tests translationField method for translated fields.
     *
     * @return void
     */
    public function testTranslationFieldForTranslatedFields()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'defaultLocale' => 'en_US'
        ]);

        $expectedSameLocale = 'Articles.title';
        $expectedOtherLocale = 'Articles_title_translation.content';

        $field = $table->translationField('title');
        $this->assertSame($expectedSameLocale, $field);

        I18n::setLocale('es_ES');
        $field = $table->translationField('title');
        $this->assertSame($expectedOtherLocale, $field);

        I18n::setLocale('en');
        $field = $table->translationField('title');
        $this->assertSame($expectedOtherLocale, $field);

        $table->removeBehavior('Translate');

        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'defaultLocale' => 'de_DE'
        ]);

        I18n::setLocale('de_DE');
        $field = $table->translationField('title');
        $this->assertSame($expectedSameLocale, $field);

        I18n::setLocale('en_US');
        $field = $table->translationField('title');
        $this->assertSame($expectedOtherLocale, $field);

        $table->locale('de_DE');
        $field = $table->translationField('title');
        $this->assertSame($expectedSameLocale, $field);

        $table->locale('es');
        $field = $table->translationField('title');
        $this->assertSame($expectedOtherLocale, $field);
    }

    /**
     * Tests translationField method for other fields.
     *
     * @return void
     */
    public function testTranslationFieldForOtherFields()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $expected = 'Articles.foo';
        $field = $table->translationField('foo');
        $this->assertSame($expected, $field);
    }

    /**
     * Tests that translating fields work when other formatters are used
     *
     * @return void
     */
    public function testFindList()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('eng');

        $results = $table->find('list')->toArray();
        $expected = [1 => 'Title #1', 2 => 'Title #2', 3 => 'Title #3'];
        $this->assertSame($expected, $results);
    }

    /**
     * Tests that the query count return the correct results
     *
     * @return void
     */
    public function testFindCount()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('eng');

        $this->assertEquals(3, $table->find()->count());
    }

    /**
     * Tests that it is possible to get all translated fields at once
     *
     * @return void
     */
    public function testFindTranslations()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $results = $table->find('translations');
        $expected = [
            [
                'eng' => ['title' => 'Title #1', 'body' => 'Content #1', 'description' => 'Description #1', 'locale' => 'eng'],
                'deu' => ['title' => 'Titel #1', 'body' => 'Inhalt #1', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #1', 'body' => 'Obsah #1', 'locale' => 'cze'],
                'spa' => ['body' => 'Contenido #1', 'locale' => 'spa', 'description' => '']
            ],
            [
                'eng' => ['title' => 'Title #2', 'body' => 'Content #2', 'locale' => 'eng'],
                'deu' => ['title' => 'Titel #2', 'body' => 'Inhalt #2', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #2', 'body' => 'Obsah #2', 'locale' => 'cze']
            ],
            [
                'eng' => ['title' => 'Title #3', 'body' => 'Content #3', 'locale' => 'eng'],
                'deu' => ['title' => 'Titel #3', 'body' => 'Inhalt #3', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #3', 'body' => 'Obsah #3', 'locale' => 'cze']
            ]
        ];

        $translations = $this->_extractTranslations($results);
        $this->assertEquals($expected, $translations->toArray());
        $expected = [
            1 => ['First Article' => 'First Article Body'],
            2 => ['Second Article' => 'Second Article Body'],
            3 => ['Third Article' => 'Third Article Body']
        ];

        $grouped = $results->combine('title', 'body', 'id');
        $this->assertEquals($expected, $grouped->toArray());
    }

    /**
     * Tests that it is possible to request just a few translations
     *
     * @return void
     */
    public function testFindFilteredTranslations()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $results = $table->find('translations', ['locales' => ['deu', 'cze']]);
        $expected = [
            [
                'deu' => ['title' => 'Titel #1', 'body' => 'Inhalt #1', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #1', 'body' => 'Obsah #1', 'locale' => 'cze']
            ],
            [
                'deu' => ['title' => 'Titel #2', 'body' => 'Inhalt #2', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #2', 'body' => 'Obsah #2', 'locale' => 'cze']
            ],
            [
                'deu' => ['title' => 'Titel #3', 'body' => 'Inhalt #3', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #3', 'body' => 'Obsah #3', 'locale' => 'cze']
            ]
        ];

        $translations = $this->_extractTranslations($results);
        $this->assertEquals($expected, $translations->toArray());

        $expected = [
            1 => ['First Article' => 'First Article Body'],
            2 => ['Second Article' => 'Second Article Body'],
            3 => ['Third Article' => 'Third Article Body']
        ];

        $grouped = $results->combine('title', 'body', 'id');
        $this->assertEquals($expected, $grouped->toArray());
    }

    /**
     * Tests that it is possible to combine find('list') and find('translations')
     *
     * @return void
     */
    public function testFindTranslationsList()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $results = $table
            ->find('list', [
                'keyField' => 'title',
                'valueField' => '_translations.deu.title',
                'groupField' => 'id'
            ])
            ->find('translations', ['locales' => ['deu']]);

        $expected = [
            1 => ['First Article' => 'Titel #1'],
            2 => ['Second Article' => 'Titel #2'],
            3 => ['Third Article' => 'Titel #3']
        ];
        $this->assertEquals($expected, $results->toArray());
    }

    /**
     * Tests that you can both override fields and find all translations
     *
     * @return void
     */
    public function testFindTranslationsWithFieldOverriding()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('cze');
        $results = $table->find('translations', ['locales' => ['deu', 'cze']]);
        $expected = [
            [
                'deu' => ['title' => 'Titel #1', 'body' => 'Inhalt #1', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #1', 'body' => 'Obsah #1', 'locale' => 'cze']
            ],
            [
                'deu' => ['title' => 'Titel #2', 'body' => 'Inhalt #2', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #2', 'body' => 'Obsah #2', 'locale' => 'cze']
            ],
            [
                'deu' => ['title' => 'Titel #3', 'body' => 'Inhalt #3', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #3', 'body' => 'Obsah #3', 'locale' => 'cze']
            ]
        ];

        $translations = $this->_extractTranslations($results);
        $this->assertEquals($expected, $translations->toArray());

        $expected = [
            1 => ['Titulek #1' => 'Obsah #1'],
            2 => ['Titulek #2' => 'Obsah #2'],
            3 => ['Titulek #3' => 'Obsah #3']
        ];

        $grouped = $results->combine('title', 'body', 'id');
        $this->assertEquals($expected, $grouped->toArray());
    }

    /**
     * Tests that fields can be overridden in a hasMany association
     *
     * @return void
     */
    public function testFindSingleLocaleHasMany()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->hasMany('Comments');
        $comments = $table->hasMany('Comments')->target();
        $comments->addBehavior('Translate', ['fields' => ['comment']]);

        $table->locale('eng');
        $comments->locale('eng');

        $results = $table->find()->contain(['Comments' => function ($q) {
            return $q->select(['id', 'comment', 'article_id']);
        }]);

        $list = new Collection($results->first()->comments);
        $expected = [
            1 => 'Comment #1',
            2 => 'Comment #2',
            3 => 'Comment #3',
            4 => 'Comment #4'
        ];
        $this->assertEquals($expected, $list->combine('id', 'comment')->toArray());
    }

    /**
     * Test that it is possible to bring translations from hasMany relations
     *
     * @return void
     */
    public function testTranslationsHasMany()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->hasMany('Comments');
        $comments = $table->hasMany('Comments')->target();
        $comments->addBehavior('Translate', ['fields' => ['comment']]);

        $results = $table->find('translations')->contain([
            'Comments' => function ($q) {
                return $q->find('translations')->select(['id', 'comment', 'article_id']);
            }
        ]);

        $comments = $results->first()->comments;
        $expected = [
            [
                'eng' => ['comment' => 'Comment #1', 'locale' => 'eng']
            ],
            [
                'eng' => ['comment' => 'Comment #2', 'locale' => 'eng']
            ],
            [
                'eng' => ['comment' => 'Comment #3', 'locale' => 'eng']
            ],
            [
                'eng' => ['comment' => 'Comment #4', 'locale' => 'eng'],
                'spa' => ['comment' => 'Comentario #4', 'locale' => 'spa']
            ]
        ];

        $translations = $this->_extractTranslations($comments);
        $this->assertEquals($expected, $translations->toArray());
    }

    /**
     * Tests that it is possible to both override fields with a translation and
     * also find separately other translations
     *
     * @return void
     */
    public function testTranslationsHasManyWithOverride()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->hasMany('Comments');
        $comments = $table->hasMany('Comments')->target();
        $comments->addBehavior('Translate', ['fields' => ['comment']]);

        $table->locale('cze');
        $comments->locale('eng');
        $results = $table->find('translations')->contain([
            'Comments' => function ($q) {
                return $q->find('translations')->select(['id', 'comment', 'article_id']);
            }
        ]);

        $comments = $results->first()->comments;
        $expected = [
            1 => 'Comment #1',
            2 => 'Comment #2',
            3 => 'Comment #3',
            4 => 'Comment #4'
        ];
        $list = new Collection($comments);
        $this->assertEquals($expected, $list->combine('id', 'comment')->toArray());

        $expected = [
            [
                'eng' => ['comment' => 'Comment #1', 'locale' => 'eng']
            ],
            [
                'eng' => ['comment' => 'Comment #2', 'locale' => 'eng']
            ],
            [
                'eng' => ['comment' => 'Comment #3', 'locale' => 'eng']
            ],
            [
                'eng' => ['comment' => 'Comment #4', 'locale' => 'eng'],
                'spa' => ['comment' => 'Comentario #4', 'locale' => 'spa']
            ]
        ];
        $translations = $this->_extractTranslations($comments);
        $this->assertEquals($expected, $translations->toArray());

        $this->assertEquals('Titulek #1', $results->first()->title);
        $this->assertEquals('Obsah #1', $results->first()->body);
    }

    /**
     * Tests that it is possible to translate belongsTo associations
     *
     * @return void
     */
    public function testFindSingleLocaleBelongsto()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $authors = $table->belongsTo('Authors')->target();
        $authors->addBehavior('Translate', ['fields' => ['name']]);

        $table->locale('eng');
        $authors->locale('eng');

        $results = $table->find()
            ->select(['title', 'body'])
            ->order(['title' => 'asc'])
            ->contain(['Authors' => function ($q) {
                return $q->select(['id', 'name']);
            }]);

        $expected = [
            [
                'title' => 'Title #1',
                'body' => 'Content #1',
                'author' => ['id' => 1, 'name' => 'May-rianoh', '_locale' => 'eng'],
                '_locale' => 'eng'
            ],
            [
                'title' => 'Title #2',
                'body' => 'Content #2',
                'author' => ['id' => 3, 'name' => 'larry', '_locale' => 'eng'],
                '_locale' => 'eng'
            ],
            [
                'title' => 'Title #3',
                'body' => 'Content #3',
                'author' => ['id' => 1, 'name' => 'May-rianoh', '_locale' => 'eng'],
                '_locale' => 'eng'
            ]
        ];
        $results = array_map(function ($r) {
            return $r->toArray();
        }, $results->toArray());
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to translate belongsToMany associations
     *
     * @return void
     */
    public function testFindSingleLocaleBelongsToMany()
    {
        $table = TableRegistry::get('Articles');
        $specialTags = TableRegistry::get('SpecialTags');
        $specialTags->addBehavior('Translate', ['fields' => ['extra_info']]);

        $table->belongsToMany('Tags', [
            'through' => $specialTags
        ]);
        $specialTags->locale('eng');

        $result = $table->get(2, ['contain' => 'Tags']);
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->tags);
        $this->assertEquals('Translated Info', $result->tags[0]->special_tags[0]->extra_info);
    }

    /**
     * Tests that updating an existing record translations work
     *
     * @return void
     */
    public function testUpdateSingleLocale()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('eng');
        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $article->set('title', 'New translated article');
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $this->assertEquals('New translated article', $article->get('title'));
        $this->assertEquals('Content #1', $article->get('body'));

        $table->locale(false);
        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $this->assertEquals('First Article', $article->get('title'));

        $table->locale('eng');
        $article->set('title', 'Wow, such translated article');
        $article->set('body', 'A translated body');
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $this->assertEquals('Wow, such translated article', $article->get('title'));
        $this->assertEquals('A translated body', $article->get('body'));
    }

    /**
     * Tests adding new translation to a record
     *
     * @return void
     */
    public function testInsertNewTranslations()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('fra');

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $article->set('title', 'Le titre');
        $table->save($article);
        $this->assertEquals('fra', $article->get('_locale'));

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $this->assertEquals('Le titre', $article->get('title'));
        $this->assertEquals('First Article Body', $article->get('body'));

        $article->set('title', 'Un autre titre');
        $article->set('body', 'Le contenu');
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertEquals('Un autre titre', $article->get('title'));
        $this->assertEquals('Le contenu', $article->get('body'));
    }

    /**
     * Tests adding new translation to a record
     *
     * @return void
     */
    public function testAllowEmptyFalse()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title'], 'allowEmptyTranslations' => false]);

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));

        $article = $table->patchEntity($article, [
            '_translations' => [
                'fra' => [
                    'title' => ''
                ]
            ]
        ]);

        $table->save($article);

        // Remove the Behavior to unset the content != '' condition
        $table->removeBehavior('Translate');

        $noFra = $table->I18n->find()->where(['locale' => 'fra'])->first();
        $this->assertEmpty($noFra);
    }

    /**
     * Tests adding new translation to a record
     *
     * @return void
     */
    public function testMixedAllowEmptyFalse()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body'], 'allowEmptyTranslations' => false]);

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));

        $article = $table->patchEntity($article, [
            '_translations' => [
                'fra' => [
                    'title' => '',
                    'body' => 'Bonjour'
                ]
            ]
        ]);

        $table->save($article);

        $fra = $table->I18n->find()
            ->where([
                'locale' => 'fra',
                'field' => 'body'
            ])
            ->first();
        $this->assertSame('Bonjour', $fra->content);

        // Remove the Behavior to unset the content != '' condition
        $table->removeBehavior('Translate');

        $noTitle = $table->I18n->find()
            ->where([
                'locale' => 'fra',
                'field' => 'title'
            ])
            ->first();
        $this->assertEmpty($noTitle);
    }

    /**
     * Tests adding new translation to a record
     *
     * @return void
     */
    public function testMultipleAllowEmptyFalse()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body'], 'allowEmptyTranslations' => false]);

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));

        $article = $table->patchEntity($article, [
            '_translations' => [
                'fra' => [
                    'title' => '',
                    'body' => 'Bonjour'
                ],
                'de' => [
                    'title' => 'Titel',
                    'body' => 'Hallo'
                ]
            ]
        ]);

        $table->save($article);

        $fra = $table->I18n->find()
            ->where([
                'locale' => 'fra',
                'field' => 'body'
            ])
            ->first();
        $this->assertSame('Bonjour', $fra->content);

        $deTitle = $table->I18n->find()
            ->where([
                'locale' => 'de',
                'field' => 'title'
            ])
            ->first();
        $this->assertSame('Titel', $deTitle->content);

        $deBody = $table->I18n->find()
            ->where([
                'locale' => 'de',
                'field' => 'body'
            ])
            ->first();
        $this->assertSame('Hallo', $deBody->content);

        // Remove the Behavior to unset the content != '' condition
        $table->removeBehavior('Translate');

        $noTitle = $table->I18n->find()
            ->where([
                'locale' => 'fra',
                'field' => 'title'
            ])
            ->first();
        $this->assertEmpty($noTitle);
    }

    /**
     * Tests that it is possible to use the _locale property to specify the language
     * to use for saving an entity
     *
     * @return void
     */
    public function testUpdateTranslationWithLocaleInEntity()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $article->set('_locale', 'fra');
        $article->set('title', 'Le titre');
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $this->assertEquals('First Article', $article->get('title'));
        $this->assertEquals('First Article Body', $article->get('body'));

        $table->locale('fra');
        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $this->assertEquals('Le titre', $article->get('title'));
        $this->assertEquals('First Article Body', $article->get('body'));
    }

    /**
     * Tests that translations are added to the whitelist of associations to be
     * saved
     *
     * @return void
     */
    public function testSaveTranslationWithAssociationWhitelist()
    {
        $table = TableRegistry::get('Articles');
        $table->hasMany('Comments');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->locale('fra');

        $article = $table->find()->first();
        $this->assertEquals(1, $article->get('id'));
        $article->set('title', 'Le titre');
        $table->save($article, ['associated' => ['Comments']]);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertEquals('Le titre', $article->get('title'));
    }

    /**
     * Tests that after deleting a translated entity, all translations are also removed
     *
     * @return void
     */
    public function testDelete()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $article = $table->find()->first();
        $this->assertTrue($table->delete($article));

        $translations = TableRegistry::get('I18n')->find()
            ->where(['model' => 'Articles', 'foreign_key' => $article->id])
            ->count();
        $this->assertEquals(0, $translations);
    }

    /**
     * Tests saving multiple translations at once when the translations already
     * exist in the database
     *
     * @return void
     */
    public function testSaveMultipleTranslations()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $article = $results = $table->find('translations')->first();

        $translations = $article->get('_translations');
        $translations['deu']->set('title', 'Another title');
        $translations['eng']->set('body', 'Another body');
        $article->set('_translations', $translations);
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $results = $table->find('translations')->first();
        $translations = $article->get('_translations');
        $this->assertEquals('Another title', $translations['deu']->get('title'));
        $this->assertEquals('Another body', $translations['eng']->get('body'));
    }

    /**
     * Tests saving multiple existing translations and adding new ones
     *
     * @return void
     */
    public function testSaveMultipleNewTranslations()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $article = $results = $table->find('translations')->first();

        $translations = $article->get('_translations');
        $translations['deu']->set('title', 'Another title');
        $translations['eng']->set('body', 'Another body');
        $translations['spa'] = new Entity(['title' => 'Titulo']);
        $translations['fre'] = new Entity(['title' => 'Titre']);
        $article->set('_translations', $translations);
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $results = $table->find('translations')->first();
        $translations = $article->get('_translations');
        $this->assertEquals('Another title', $translations['deu']->get('title'));
        $this->assertEquals('Another body', $translations['eng']->get('body'));
        $this->assertEquals('Titulo', $translations['spa']->get('title'));
        $this->assertEquals('Titre', $translations['fre']->get('title'));
    }

    /**
     * Tests that iterating a resultset twice when using the translations finder
     * will not cause any errors nor information loss
     *
     * @return void
     */
    public function testUseCountInFindTranslations()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $articles = $results = $table->find('translations');
        $all = $articles->all();
        $this->assertCount(3, $all);
        $article = $all->first();
        $this->assertNotEmpty($article->get('_translations'));
    }

    /**
     * Tests that multiple translations saved when having a default locale
     * are correctly saved
     *
     * @return void
     */
    public function testSavingWithNonDefaultLocale()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->entityClass(__NAMESPACE__ . '\Article');
        I18n::setLocale('fra');
        $translations = [
            'fra' => ['title' => 'Un article'],
            'spa' => ['title' => 'Un artículo']
        ];

        $article = $table->get(1);
        foreach ($translations as $lang => $data) {
            $article->translation($lang)->set($data, ['guard' => false]);
        }

        $table->save($article);
        $article = $table->find('translations')->where(['Articles.id' => 1])->first();
        $this->assertEquals('Un article', $article->translation('fra')->title);
        $this->assertEquals('Un artículo', $article->translation('spa')->title);
    }

    /**
     * Tests that translation queries are added to union queries as well.
     *
     * @return void
     */
    public function testTranslationWithUnionQuery()
    {
        $table = TableRegistry::get('Comments');
        $table->addBehavior('Translate', ['fields' => ['comment']]);
        $table->locale('spa');
        $query = $table->find()->where(['Comments.id' => 6]);
        $query2 = $table->find()->where(['Comments.id' => 5]);
        $query->union($query2);
        $results = $query->sortBy('id', SORT_ASC)->toList();
        $this->assertCount(2, $results);

        $this->assertEquals('First Comment for Second Article', $results[0]->comment);
        $this->assertEquals('Second Comment for Second Article', $results[1]->comment);
    }

    /**
     * Tests the use of `referenceName` config option.
     *
     * @return void
     */
    public function testAutoReferenceName()
    {
        $table = TableRegistry::get('Articles');

        $table->hasMany('OtherComments', ['className' => 'Comments']);
        $table->OtherComments->addBehavior(
            'Translate',
            ['fields' => ['comment']]
        );

        $items = $table->OtherComments->associations();
        $association = $items->getByProperty('comment_translation');
        $this->assertNotEmpty($association, 'Translation association not found');

        $found = false;
        foreach ($association->conditions() as $key => $value) {
            if (strpos($key, 'comment_translation.model') !== false) {
                $found = true;
                $this->assertEquals('Comments', $value);
                break;
            }
        }

        $this->assertTrue($found, '`referenceName` field condition on a Translation association was not found');
    }

    /**
     * Tests the use of unconventional `referenceName` config option.
     *
     * @return void
     */
    public function testChangingReferenceName()
    {
        $table = TableRegistry::get('Articles');
        $table->alias('FavoritePost');
        $table->addBehavior(
            'Translate',
            ['fields' => ['body'], 'referenceName' => 'Posts']
        );

        $items = $table->associations();
        $association = $items->getByProperty('body_translation');
        $this->assertNotEmpty($association, 'Translation association not found');

        $found = false;
        foreach ($association->conditions() as $key => $value) {
            if (strpos($key, 'body_translation.model') !== false) {
                $found = true;
                $this->assertEquals('Posts', $value);
                break;
            }
        }

        $this->assertTrue($found, '`referenceName` field condition on a Translation association was not found');
    }

    /**
     * Tests that onlyTranslated will remove records from the result set
     * if they are not fully translated
     *
     * @return void
     */
    public function testFilterUntranslated()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'onlyTranslated' => true
        ]);
        $table->locale('eng');
        $results = $table->find()->where(['Articles.id' => 1])->all();
        $this->assertCount(1, $results);

        $table->locale('fr');
        $results = $table->find()->where(['Articles.id' => 1])->all();
        $this->assertCount(0, $results);
    }

    /**
     * Tests that records not translated in the current locale will not be
     * present in the results for the translations finder, and also proves
     * that this can be overridden.
     *
     * @return void
     */
    public function testFilterUntranslatedWithFinder()
    {
        $table = TableRegistry::get('Comments');
        $table->addBehavior('Translate', [
            'fields' => ['comment'],
            'onlyTranslated' => true
        ]);
        $table->locale('eng');
        $results = $table->find('translations')->all();
        $this->assertCount(4, $results);

        $table->locale('spa');
        $results = $table->find('translations')->all();
        $this->assertCount(1, $results);

        $table->locale('spa');
        $results = $table->find('translations', ['filterByCurrentLocale' => false])->all();
        $this->assertCount(6, $results);

        $table->locale('spa');
        $results = $table->find('translations')->all();
        $this->assertCount(1, $results);
    }

    /**
     * Tests that allowEmptyTranslations takes effect
     *
     * @return void
     */
    public function testEmptyTranslations()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body', 'description'],
            'allowEmptyTranslations' => false,
        ]);
        $table->locale('spa');
        $result = $table->find()->first();
        $this->assertNull($result->description);
    }

    /**
     * Test save with clean translate fields
     *
     * @return void
     */
    public function testSaveWithCleanFields()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title']]);
        $table->entityClass(__NAMESPACE__ . '\Article');
        I18n::setLocale('fra');
        $article = $table->get(1);
        $article->set('body', 'New Body');
        $table->save($article);
        $result = $table->get(1);
        $this->assertEquals('New Body', $result->body);
        $this->assertSame($article->title, $result->title);
    }

    /**
     * Test save new entity with _translations field
     *
     * @return void
     */
    public function testSaveNewRecordWithTranslatesField()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title'],
            'validator' => (new \Cake\Validation\Validator)->add('title', 'notBlank', ['rule' => 'notBlank'])
        ]);
        $table->entityClass(__NAMESPACE__ . '\Article');

        $data = [
            'author_id' => 1,
            'published' => 'N',
            '_translations' => [
                'en' => [
                    'title' => 'Title EN',
                    'body' => 'Body EN'
                ],
                'es' => [
                    'title' => 'Title ES'
                ]
            ]
        ];

        $article = $table->patchEntity($table->newEntity(), $data);
        $result = $table->save($article);

        $this->assertNotFalse($result);

        $expected = [
            [
                'en' => [
                    'title' => 'Title EN',
                    'locale' => 'en'
                ],
                'es' => [
                    'title' => 'Title ES',
                    'locale' => 'es'
                ]
            ]
        ];
        $result = $table->find('translations')->where(['id' => $result->id]);
        $this->assertEquals($expected, $this->_extractTranslations($result)->toArray());
    }

    /**
     * Tests adding new translation to a record where the only field is the translated one and it's not the default locale
     *
     * @return void
     */
    public function testSaveNewRecordWithOnlyTranslationsNotDefaultLocale()
    {
        $table = TableRegistry::get('Groups');
        $table->addBehavior('Translate', [
            'fields' => ['title'],
            'validator' => (new \Cake\Validation\Validator)->add('title', 'notBlank', ['rule' => 'notBlank'])
        ]);

        $data = [
            '_translations' => [
                'es' => [
                    'title' => 'Title ES'
                ]
            ]
        ];

        $group = $table->newEntity($data);
        $result = $table->save($group);
        $this->assertNotFalse($result, 'Record should save.');

        $expected = [
            [
                'es' => [
                    'title' => 'Title ES',
                    'locale' => 'es'
                ]
            ]
        ];
        $result = $table->find('translations')->where(['id' => $result->id]);
        $this->assertEquals($expected, $this->_extractTranslations($result)->toArray());
    }

    /**
     * Test that existing records can be updated when only translations
     * are modified/dirty.
     *
     * @return void
     */
    public function testSaveExistingRecordOnlyTranslations()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->entityClass(__NAMESPACE__ . '\Article');

        $data = [
            '_translations' => [
                'es' => [
                    'title' => 'Spanish Translation',
                ],
            ]
        ];

        $article = $table->find()->first();
        $article = $table->patchEntity($article, $data);

        $this->assertNotFalse($table->save($article));

        $results = $this->_extractTranslations(
            $table->find('translations')->where(['id' => 1])
        )->first();

        $this->assertArrayHasKey('es', $results, 'New translation added');
        $this->assertArrayHasKey('eng', $results, 'Old translations present');
        $this->assertEquals('Spanish Translation', $results['es']['title']);
    }

    /**
     * Test update entity with _translations field.
     *
     * @return void
     */
    public function testSaveExistingRecordWithTranslatesField()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->entityClass(__NAMESPACE__ . '\Article');

        $data = [
            'author_id' => 1,
            'published' => 'Y',
            '_translations' => [
                'eng' => [
                    'title' => 'First Article1',
                    'body' => 'First Article content has been updated'
                ],
                'spa' => [
                    'title' => 'Mi nuevo titulo',
                    'body' => 'Contenido Actualizado'
                ]
            ]
        ];

        $article = $table->find()->first();
        $article = $table->patchEntity($article, $data);

        $this->assertNotFalse($table->save($article));

        $results = $this->_extractTranslations(
            $table->find('translations')->where(['id' => 1])
        )->first();

        $this->assertEquals('Mi nuevo titulo', $results['spa']['title']);
        $this->assertEquals('Contenido Actualizado', $results['spa']['body']);

        $this->assertEquals('First Article1', $results['eng']['title']);
        $this->assertEquals('Description #1', $results['eng']['description']);
    }

    /**
     * Tests that default locale saves ok.
     *
     * @return void
     */
    public function testSaveDefaultLocale()
    {
        $table = TableRegistry::get('Articles');
        $table->hasMany('Comments');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $article = $table->get(1);
        $data = [
            'title' => 'New title',
            'body' => 'New body',
        ];
        $article = $table->patchEntity($article, $data);
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->get(1);
        $this->assertEquals('New title', $article->get('title'));
        $this->assertEquals('New body', $article->get('body'));
    }

    /**
     * Tests that translations are added to the whitelist of associations to be
     * saved
     *
     * @return void
     */
    public function testSaveTranslationDefaultLocale()
    {
        $table = TableRegistry::get('Articles');
        $table->hasMany('Comments');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $article = $table->get(1);
        $data = [
            'title' => 'New title',
            'body' => 'New body',
            '_translations' => [
                'es' => [
                    'title' => 'ES title',
                    'body' => 'ES body'
                ]
            ]
        ];
        $article = $table->patchEntity($article, $data);
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find('translations')->where(['id' => 1])->first();
        $this->assertEquals('New title', $article->get('title'));
        $this->assertEquals('New body', $article->get('body'));

        $this->assertEquals('ES title', $article->_translations['es']->title);
        $this->assertEquals('ES body', $article->_translations['es']->body);
    }

    /**
     * Test that no properties are enabled when the translations
     * option is off.
     *
     * @return void
     */
    public function testBuildMarshalMapTranslationsOff()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $marshaller = $table->marshaller();
        $translate = $table->behaviors()->get('Translate');
        $result = $translate->buildMarshalMap($marshaller, [], ['translations' => false]);
        $this->assertSame([], $result);
    }

    /**
     * Test building a marshal map with translations on.
     *
     * @return void
     */
    public function testBuildMarshalMapTranslationsOn()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $marshaller = $table->marshaller();
        $translate = $table->behaviors()->get('Translate');

        $result = $translate->buildMarshalMap($marshaller, [], ['translations' => true]);
        $this->assertArrayHasKey('_translations', $result);
        $this->assertInstanceOf('Closure', $result['_translations']);

        $result = $translate->buildMarshalMap($marshaller, [], []);
        $this->assertArrayHasKey('_translations', $result);
        $this->assertInstanceOf('Closure', $result['_translations']);
    }

    /**
     * Test marshalling non-array data
     *
     * @return void
     */
    public function testBuildMarshalMapNonArrayData()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $translate = $table->behaviors()->get('Translate');

        $map = $translate->buildMarshalMap($table->marshaller(), [], []);
        $entity = $table->newEntity();
        $result = $map['_translations']('garbage', $entity);
        $this->assertNull($result, 'Non-array should not error out.');
        $this->assertEmpty($entity->errors());
        $this->assertEmpty($entity->get('_translations'));
    }

    /**
     * Test buildMarshalMap() builds new entities.
     *
     * @return void
     */
    public function testBuildMarshalMapBuildEntities()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $translate = $table->behaviors()->get('Translate');

        $map = $translate->buildMarshalMap($table->marshaller(), [], []);
        $entity = $table->newEntity();
        $data = [
            'en' => [
                'title' => 'English Title',
                'body' => 'English Content'
            ],
            'es' => [
                'title' => 'Titulo Español',
                'body' => 'Contenido Español'
            ]
        ];
        $result = $map['_translations']($data, $entity);
        $this->assertEmpty($entity->errors(), 'No validation errors.');
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('es', $result);
        $this->assertEquals('English Title', $result['en']->title);
        $this->assertEquals('Titulo Español', $result['es']->title);
    }

    /**
     * Test that validation errors are added to the original entity.
     *
     * @return void
     */
    public function testBuildMarshalMapBuildEntitiesValidationErrors()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'validator' => 'custom'
        ]);
        $validator = (new Validator)->add('title', 'notBlank', ['rule' => 'notBlank']);
        $table->validator('custom', $validator);
        $translate = $table->behaviors()->get('Translate');

        $entity = $table->newEntity();
        $map = $translate->buildMarshalMap($table->marshaller(), [], []);
        $data = [
            'en' => [
                'title' => 'English Title',
                'body' => 'English Content'
            ],
            'es' => [
                'title' => '',
                'body' => 'Contenido Español'
            ]
        ];
        $result = $map['_translations']($data, $entity);
        $this->assertNotEmpty($entity->errors(), 'Needs validation errors.');
        $expected = [
            'title' => [
                '_empty' => 'This field cannot be left empty'
            ]
        ];
        $this->assertEquals($expected, $entity->errors('es'));

        $this->assertEquals('English Title', $result['en']->title);
        $this->assertEquals('', $result['es']->title);
    }

    /**
     * Test that marshalling updates existing translation entities.
     *
     * @return void
     */
    public function testBuildMarshalMapUpdateExistingEntities()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
        ]);
        $translate = $table->behaviors()->get('Translate');

        $entity = $table->newEntity();
        $es = $table->newEntity(['title' => 'Old title', 'body' => 'Old body']);
        $en = $table->newEntity(['title' => 'Old title', 'body' => 'Old body']);
        $entity->set('_translations', [
            'es' => $es,
            'en' => $en,
        ]);
        $map = $translate->buildMarshalMap($table->marshaller(), [], []);
        $data = [
            'en' => [
                'title' => 'English Title',
            ],
            'es' => [
                'title' => 'Spanish Title',
            ]
        ];
        $result = $map['_translations']($data, $entity);
        $this->assertEmpty($entity->errors(), 'No validation errors.');
        $this->assertSame($en, $result['en']);
        $this->assertSame($es, $result['es']);
        $this->assertSame($en, $entity->get('_translations')['en']);
        $this->assertSame($es, $entity->get('_translations')['es']);

        $this->assertEquals('English Title', $result['en']->title);
        $this->assertEquals('Spanish Title', $result['es']->title);
        $this->assertEquals('Old body', $result['en']->body);
        $this->assertEquals('Old body', $result['es']->body);
    }

    /**
     * Test that updating translation records works with validations.
     *
     * @return void
     */
    public function testBuildMarshalMapUpdateEntitiesValidationErrors()
    {
        $table = TableRegistry::get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'validator' => 'custom'
        ]);
        $validator = (new Validator)->add('title', 'notBlank', ['rule' => 'notBlank']);
        $table->validator('custom', $validator);
        $translate = $table->behaviors()->get('Translate');

        $entity = $table->newEntity();
        $es = $table->newEntity(['title' => 'Old title', 'body' => 'Old body']);
        $en = $table->newEntity(['title' => 'Old title', 'body' => 'Old body']);
        $entity->set('_translations', [
            'es' => $es,
            'en' => $en,
        ]);
        $map = $translate->buildMarshalMap($table->marshaller(), [], []);
        $data = [
            'en' => [
                'title' => 'English Title',
                'body' => 'English Content'
            ],
            'es' => [
                'title' => '',
                'body' => 'Contenido Español'
            ]
        ];
        $result = $map['_translations']($data, $entity);
        $this->assertNotEmpty($entity->errors(), 'Needs validation errors.');
        $expected = [
            'title' => [
                '_empty' => 'This field cannot be left empty'
            ]
        ];
        $this->assertEquals($expected, $entity->errors('es'));
    }
}
