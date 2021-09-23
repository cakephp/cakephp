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

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\QueryInterface;
use Cake\I18n\I18n;
use Cake\ORM\Entity;
use Cake\ORM\Locator\TableLocator;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use TestApp\Model\Entity\TranslateArticle;
use TestApp\Model\Table\CustomI18nTable;

/**
 * Translate behavior test case
 */
class TranslateBehaviorTest extends TestCase
{
    /**
     * fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
        'core.Authors',
        'core.Sections',
        'core.SpecialTags',
        'core.Comments',
        'core.Translates',
    ];

    public function tearDown(): void
    {
        parent::tearDown();
        I18n::setLocale(I18n::getDefaultLocale());
    }

    /**
     * Returns an array with all the translations found for a set of records
     *
     * @param \Traversable|array $data
     */
    protected function _extractTranslations($data): CollectionInterface
    {
        return (new Collection($data))->map(function (EntityInterface $row) {
            $translations = $row->get('_translations');
            if (!$translations) {
                return [];
            }

            return array_map(function (EntityInterface $entity) {
                return $entity->toArray();
            }, $translations);
        });
    }

    /**
     * Tests that custom translation tables are respected
     */
    public function testCustomTranslationTable(): void
    {
        ConnectionManager::setConfig('custom_i18n_datasource', ['url' => getenv('DB_URL')]);

        $table = $this->getTableLocator()->get('Articles');

        $table->addBehavior('Translate', [
            'translationTable' => CustomI18nTable::class,
            'fields' => ['title', 'body'],
        ]);

        $items = $table->associations();
        $i18n = $items->getByProperty('_i18n');

        $this->assertSame('CustomI18n', $i18n->getName());
        $this->assertInstanceOf(CustomI18nTable::class, $i18n->getTarget());
        $this->assertSame('custom_i18n_datasource', $i18n->getTarget()->getConnection()->configName());
        $this->assertSame('custom_i18n_table', $i18n->getTarget()->getTable());

        ConnectionManager::drop('custom_i18n_datasource');
    }

    /**
     * Tests that the strategy can be changed for i18n
     */
    public function testStrategy(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $table->addBehavior('Translate', [
            'strategy' => 'select',
            'fields' => ['title', 'body'],
        ]);

        $items = $table->associations();
        $i18n = $items->getByProperty('_i18n');

        $this->assertSame('select', $i18n->getStrategy());
    }

    /**
     * Tests that fields from a translated model are overridden
     */
    public function testFindSingleLocale(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $table->setLocale('eng');
        $results = $table->find()->all()->combine('title', 'body', 'id')->toArray();
        $expected = [
            1 => ['Title #1' => 'Content #1'],
            2 => ['Title #2' => 'Content #2'],
            3 => ['Title #3' => 'Content #3'],
        ];
        $this->assertSame($expected, $results);

        $entity = $table->newEntity(['author_id' => 2, 'title' => 'Title 4', 'body' => 'Body 4']);
        $table->save($entity);

        $results = $table->find('all', ['locale' => 'cze'])
            ->select(['id', 'title', 'body'])
            ->disableHydration()
            ->orderAsc('Articles.id')
            ->toArray();
        $expected = [
            ['id' => 1, 'title' => 'Titulek #1', 'body' => 'Obsah #1', '_locale' => 'cze'],
            ['id' => 2, 'title' => 'Titulek #2', 'body' => 'Obsah #2', '_locale' => 'cze'],
            ['id' => 3, 'title' => 'Titulek #3', 'body' => 'Obsah #3', '_locale' => 'cze'],
            ['id' => 4, 'title' => null, 'body' => null, '_locale' => 'cze'],
        ];
        $this->assertSame($expected, $results);
    }

    /**
     * Test that iterating in a formatResults() does not drop data.
     */
    public function testFindTranslationsFormatResultsIteration(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('eng');
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
     * and that it propagates to associated models
     */
    public function testFindSingleLocaleAssociatedEnv(): void
    {
        I18n::setLocale('eng');

        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $table->hasMany('Comments');
        $table->Comments->addBehavior('Translate', ['fields' => ['comment']]);

        $results = $table->find()
            ->select(['id', 'title', 'body'])
            ->contain(['Comments' => ['fields' => ['article_id', 'comment']]])
            ->enableHydration(false)
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
                    ['article_id' => 1, 'comment' => 'Comment #4', '_locale' => 'eng'],
                ],
                '_locale' => 'eng',
            ],
            [
                'id' => 2,
                'title' => 'Title #2',
                'body' => 'Content #2',
                'comments' => [
                    ['article_id' => 2, 'comment' => 'First Comment for Second Article', '_locale' => 'eng'],
                    ['article_id' => 2, 'comment' => 'Second Comment for Second Article', '_locale' => 'eng'],
                ],
                '_locale' => 'eng',
            ],
            [
                'id' => 3,
                'title' => 'Title #3',
                'body' => 'Content #3',
                'comments' => [],
                '_locale' => 'eng',
            ],
        ];
        $this->assertSame($expected, $results);

        I18n::setLocale('spa');

        $results = $table->find()
            ->select(['id', 'title', 'body'])
            ->contain([
                'Comments' => [
                    'fields' => ['article_id', 'comment'],
                    'sort' => ['Comments.id' => 'ASC'],
                ],
            ])
            ->enableHydration(false)
            ->orderAsc('Articles.id')
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
                    ['article_id' => 1, 'comment' => 'Comentario #4', '_locale' => 'spa'],
                ],
                '_locale' => 'spa',
            ],
            [
                'id' => 2,
                'title' => 'Second Article',
                'body' => 'Second Article Body',
                'comments' => [
                    ['article_id' => 2, 'comment' => 'First Comment for Second Article', '_locale' => 'spa'],
                    ['article_id' => 2, 'comment' => 'Second Comment for Second Article', '_locale' => 'spa'],
                ],
                '_locale' => 'spa',
            ],
            [
                'id' => 3,
                'title' => 'Third Article',
                'body' => 'Third Article Body',
                'comments' => [],
                '_locale' => 'spa',
            ],
        ];
        $this->assertSame($expected, $results);
    }

    /**
     * Tests that fields from a translated model are not overridden if translation
     * is null
     */
    public function testFindSingleLocaleWithNullTranslation(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $table->addBehavior('Translate', ['fields' => ['comment']]);
        $table->setLocale('spa');
        $results = $table->find()
            ->where(['Comments.id' => 6])
            ->all()
            ->combine('id', 'comment')
            ->toArray();
        $expected = [6 => 'Second Comment for Second Article'];
        $this->assertSame($expected, $results);
    }

    /**
     * Tests that overriding fields with the translate behavior works when
     * using conditions and that all other columns are preserved
     */
    public function testFindSingleLocaleWithgetConditions(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('eng');
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
            '_locale' => 'eng',
        ];
        $this->assertEquals($expected, $row->toArray());
    }

    /**
     * Tests the locale setter/getter.
     */
    public function testSetGetLocale(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate');

        $this->assertSame('en_US', $table->getLocale());

        $table->setLocale('fr_FR');
        $this->assertSame('fr_FR', $table->getLocale());

        $table->setLocale(null);
        $this->assertSame('en_US', $table->getLocale());

        I18n::setLocale('fr_FR');
        $this->assertSame('fr_FR', $table->getLocale());
    }

    /**
     * Tests translationField method for translated fields.
     */
    public function testTranslationFieldForTranslatedFields(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'defaultLocale' => 'en_US',
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
            'defaultLocale' => 'de_DE',
        ]);

        I18n::setLocale('de_DE');
        $field = $table->translationField('title');
        $this->assertSame($expectedSameLocale, $field);

        I18n::setLocale('en_US');
        $field = $table->translationField('title');
        $this->assertSame($expectedOtherLocale, $field);

        $table->setLocale('de_DE');
        $field = $table->translationField('title');
        $this->assertSame($expectedSameLocale, $field);

        $table->setLocale('es');
        $field = $table->translationField('title');
        $this->assertSame($expectedOtherLocale, $field);
    }

    /**
     * Tests translationField method for other fields.
     */
    public function testTranslationFieldForOtherFields(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $expected = 'Articles.foo';
        $field = $table->translationField('foo');
        $this->assertSame($expected, $field);
    }

    /**
     * Tests that translating fields work when other formatters are used
     */
    public function testFindList(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('eng');

        $results = $table->find('list')->toArray();
        $expected = [1 => 'Title #1', 2 => 'Title #2', 3 => 'Title #3'];
        $this->assertSame($expected, $results);
    }

    /**
     * Tests that the query count return the correct results
     */
    public function testFindCount(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('eng');

        $this->assertSame(3, $table->find()->count());
    }

    /**
     * Tests that it is possible to get all translated fields at once
     */
    public function testFindTranslations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $results = $table->find('translations');
        $expected = [
            [
                'eng' => ['title' => 'Title #1', 'body' => 'Content #1', 'description' => 'Description #1', 'locale' => 'eng'],
                'deu' => ['title' => 'Titel #1', 'body' => 'Inhalt #1', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #1', 'body' => 'Obsah #1', 'locale' => 'cze'],
                'spa' => ['body' => 'Contenido #1', 'locale' => 'spa', 'description' => ''],
            ],
            [
                'eng' => ['title' => 'Title #2', 'body' => 'Content #2', 'locale' => 'eng'],
                'deu' => ['title' => 'Titel #2', 'body' => 'Inhalt #2', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #2', 'body' => 'Obsah #2', 'locale' => 'cze'],
            ],
            [
                'eng' => ['title' => 'Title #3', 'body' => 'Content #3', 'locale' => 'eng'],
                'deu' => ['title' => 'Titel #3', 'body' => 'Inhalt #3', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #3', 'body' => 'Obsah #3', 'locale' => 'cze'],
            ],
        ];

        $translations = $this->_extractTranslations($results);
        $this->assertEquals($expected, $translations->toArray());
        $expected = [
            1 => ['First Article' => 'First Article Body'],
            2 => ['Second Article' => 'Second Article Body'],
            3 => ['Third Article' => 'Third Article Body'],
        ];

        $grouped = $results->all()->combine('title', 'body', 'id');
        $this->assertEquals($expected, $grouped->toArray());
    }

    /**
     * Tests that it is possible to request just a few translations
     */
    public function testFindFilteredTranslations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $results = $table->find('translations', ['locales' => ['deu', 'cze']]);
        $expected = [
            [
                'deu' => ['title' => 'Titel #1', 'body' => 'Inhalt #1', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #1', 'body' => 'Obsah #1', 'locale' => 'cze'],
            ],
            [
                'deu' => ['title' => 'Titel #2', 'body' => 'Inhalt #2', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #2', 'body' => 'Obsah #2', 'locale' => 'cze'],
            ],
            [
                'deu' => ['title' => 'Titel #3', 'body' => 'Inhalt #3', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #3', 'body' => 'Obsah #3', 'locale' => 'cze'],
            ],
        ];

        $translations = $this->_extractTranslations($results);
        $this->assertEquals($expected, $translations->toArray());

        $expected = [
            1 => ['First Article' => 'First Article Body'],
            2 => ['Second Article' => 'Second Article Body'],
            3 => ['Third Article' => 'Third Article Body'],
        ];

        $grouped = $results->all()->combine('title', 'body', 'id');
        $this->assertEquals($expected, $grouped->toArray());
    }

    /**
     * Tests that it is possible to combine find('list') and find('translations')
     */
    public function testFindTranslationsList(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $results = $table
            ->find('list', [
                'keyField' => 'title',
                'valueField' => '_translations.deu.title',
                'groupField' => 'id',
            ])
            ->find('translations', ['locales' => ['deu']]);

        $expected = [
            1 => ['First Article' => 'Titel #1'],
            2 => ['Second Article' => 'Titel #2'],
            3 => ['Third Article' => 'Titel #3'],
        ];
        $this->assertEquals($expected, $results->toArray());
    }

    /**
     * Tests that you can both override fields and find all translations
     */
    public function testFindTranslationsWithFieldOverriding(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('cze');
        $results = $table->find('translations', ['locales' => ['deu', 'cze']]);
        $expected = [
            [
                'deu' => ['title' => 'Titel #1', 'body' => 'Inhalt #1', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #1', 'body' => 'Obsah #1', 'locale' => 'cze'],
            ],
            [
                'deu' => ['title' => 'Titel #2', 'body' => 'Inhalt #2', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #2', 'body' => 'Obsah #2', 'locale' => 'cze'],
            ],
            [
                'deu' => ['title' => 'Titel #3', 'body' => 'Inhalt #3', 'locale' => 'deu'],
                'cze' => ['title' => 'Titulek #3', 'body' => 'Obsah #3', 'locale' => 'cze'],
            ],
        ];

        $translations = $this->_extractTranslations($results);
        $this->assertEquals($expected, $translations->toArray());

        $expected = [
            1 => ['Titulek #1' => 'Obsah #1'],
            2 => ['Titulek #2' => 'Obsah #2'],
            3 => ['Titulek #3' => 'Obsah #3'],
        ];

        $grouped = $results->all()->combine('title', 'body', 'id');
        $this->assertEquals($expected, $grouped->toArray());
    }

    /**
     * Tests that fields can be overridden in a hasMany association
     */
    public function testFindSingleLocaleHasMany(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->hasMany('Comments');
        $comments = $table->hasMany('Comments')->getTarget();
        $comments->addBehavior('Translate', ['fields' => ['comment']]);

        $table->setLocale('eng');
        $comments->setLocale('eng');

        $results = $table->find()->contain(['Comments' => function ($q) {
            return $q->select(['id', 'comment', 'article_id']);
        }]);

        $list = new Collection($results->first()->comments);
        $expected = [
            1 => 'Comment #1',
            2 => 'Comment #2',
            3 => 'Comment #3',
            4 => 'Comment #4',
        ];
        $this->assertEquals($expected, $list->combine('id', 'comment')->toArray());
    }

    /**
     * Test that it is possible to bring translations from hasMany relations
     */
    public function testTranslationsHasMany(): void
    {
        // This test fails on mysql8 + php8 due to no data in the tables
        // We have been unable to explain the behavior so disabling for now
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf(
            $driver instanceof Mysql &&
            version_compare($driver->version(), '8.0.0', '>=')
        );

        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->hasMany('Comments');
        $comments = $table->hasMany('Comments')->getTarget();
        $comments->addBehavior('Translate', ['fields' => ['comment']]);

        $results = $table->find('translations')->contain([
            'Comments' => function ($q) {
                return $q->find('translations')->select(['id', 'comment', 'article_id']);
            },
        ]);

        $comments = $results->first()->comments;
        $expected = [
            [
                'eng' => ['comment' => 'Comment #1', 'locale' => 'eng'],
            ],
            [
                'eng' => ['comment' => 'Comment #2', 'locale' => 'eng'],
            ],
            [
                'eng' => ['comment' => 'Comment #3', 'locale' => 'eng'],
            ],
            [
                'eng' => ['comment' => 'Comment #4', 'locale' => 'eng'],
                'spa' => ['comment' => 'Comentario #4', 'locale' => 'spa'],
            ],
        ];

        $translations = $this->_extractTranslations($comments);
        $this->assertEquals($expected, $translations->toArray());
    }

    /**
     * Tests that it is possible to both override fields with a translation and
     * also find separately other translations
     */
    public function testTranslationsHasManyWithOverride(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->hasMany('Comments');
        $comments = $table->hasMany('Comments')->getTarget();
        $comments->addBehavior('Translate', ['fields' => ['comment']]);

        $table->setLocale('cze');
        $comments->setLocale('eng');
        $results = $table->find('translations')->contain([
            'Comments' => function ($q) {
                return $q->find('translations')->select(['id', 'comment', 'article_id']);
            },
        ]);

        $comments = $results->first()->comments;
        $expected = [
            1 => 'Comment #1',
            2 => 'Comment #2',
            3 => 'Comment #3',
            4 => 'Comment #4',
        ];
        $list = new Collection($comments);
        $this->assertEquals($expected, $list->combine('id', 'comment')->toArray());

        $expected = [
            [
                'eng' => ['comment' => 'Comment #1', 'locale' => 'eng'],
            ],
            [
                'eng' => ['comment' => 'Comment #2', 'locale' => 'eng'],
            ],
            [
                'eng' => ['comment' => 'Comment #3', 'locale' => 'eng'],
            ],
            [
                'eng' => ['comment' => 'Comment #4', 'locale' => 'eng'],
                'spa' => ['comment' => 'Comentario #4', 'locale' => 'spa'],
            ],
        ];
        $translations = $this->_extractTranslations($comments);
        $this->assertEquals($expected, $translations->toArray());

        $this->assertSame('Titulek #1', $results->first()->title);
        $this->assertSame('Obsah #1', $results->first()->body);
    }

    /**
     * Tests that it is possible to translate belongsTo associations
     */
    public function testFindSingleLocaleBelongsto(): void
    {
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $table */
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $authors */
        $authors = $table->belongsTo('Authors')->getTarget();
        $authors->addBehavior('Translate', ['fields' => ['name']]);

        $table->setLocale('eng');
        $authors->setLocale('eng');

        $results = $table->find()
            ->select(['title', 'body'])
            ->order(['title' => 'asc'])
            ->contain(['Authors' => function (QueryInterface $q) {
                return $q->select(['id', 'name']);
            }]);

        $expected = [
            [
                'title' => 'Title #1',
                'body' => 'Content #1',
                'author' => ['id' => 1, 'name' => 'May-rianoh', '_locale' => 'eng'],
                '_locale' => 'eng',
            ],
            [
                'title' => 'Title #2',
                'body' => 'Content #2',
                'author' => ['id' => 3, 'name' => 'larry', '_locale' => 'eng'],
                '_locale' => 'eng',
            ],
            [
                'title' => 'Title #3',
                'body' => 'Content #3',
                'author' => ['id' => 1, 'name' => 'May-rianoh', '_locale' => 'eng'],
                '_locale' => 'eng',
            ],
        ];
        $results = array_map(function (EntityInterface $r) {
            return $r->toArray();
        }, $results->toArray());
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to translate belongsTo associations using loadInto
     */
    public function testFindSingleLocaleBelongstoLoadInto(): void
    {
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $table */
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $authors */
        $authors = $table->belongsTo('Authors')->getTarget();
        $authors->addBehavior('Translate', ['fields' => ['name']]);

        $table->setLocale('eng');
        $authors->setLocale('eng');

        $entity = $table->get(1);
        $result = $table->loadInto($entity, ['Authors']);
        $this->assertSame($entity, $result);
        $this->assertNotEmpty($entity->author);
        $this->assertNotEmpty($entity->author->name);

        $expected = $table->get(1, ['contain' => ['Authors']]);
        $this->assertEquals($expected, $result);
        $this->assertNotEmpty($entity->author);
        $this->assertNotEmpty($entity->author->name);
    }

    /**
     * Tests that it is possible to translate belongsToMany associations
     */
    public function testFindSingleLocaleBelongsToMany(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $specialTags */
        $specialTags = $this->getTableLocator()->get('SpecialTags');
        $specialTags->addBehavior('Translate', ['fields' => ['extra_info']]);

        $table->belongsToMany('Tags', [
            'through' => $specialTags,
        ]);
        $specialTags->setLocale('eng');

        $result = $table->get(2, ['contain' => 'Tags']);
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->tags);
        $this->assertSame('Translated Info', $result->tags[0]->special_tags[0]->extra_info);
    }

    /**
     * Tests that parent entity isn't dirty when containing a translated association
     */
    public function testGetAssociationNotDirtyBelongsTo(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $authors */
        $authors = $table->belongsTo('Authors')->getTarget();
        $authors->addBehavior('Translate', ['fields' => ['name']]);

        $authors->setLocale('eng');

        $entity = $table->get(1);
        $this->assertNotEmpty($entity);
        $entity = $table->loadInto($entity, ['Authors']);
        $this->assertFalse($entity->isDirty());
        $this->assertNotEmpty($entity->author);
        $this->assertFalse($entity->author->isDirty());

        $entity = $table->get(1, ['contain' => ['Authors']]);
        $this->assertNotEmpty($entity);
        $this->assertFalse($entity->isDirty());
        $this->assertNotEmpty($entity->author);
        $this->assertFalse($entity->author->isDirty());
    }

    /**
     * Tests that parent entity isn't dirty when containing a translated association
     */
    public function testGetAssociationNotDirtyHasOne(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasOne('Articles');
        $table->Articles->addBehavior('Translate', ['fields' => ['title']]);

        $entity = $table->get(1);
        $this->assertNotEmpty($entity);
        $entity = $table->loadInto($entity, ['Articles']);
        $this->assertFalse($entity->isDirty());
        $this->assertNotEmpty($entity->article);
        $this->assertFalse($entity->article->isDirty());

        $entity = $table->get(1, ['contain' => 'Articles']);
        $this->assertNotEmpty($entity);
        $this->assertFalse($entity->isDirty());
        $this->assertNotEmpty($entity->article);
        $this->assertFalse($entity->article->isDirty());
    }

    /**
     * Tests that updating an existing record translations work
     */
    public function testUpdateSingleLocale(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('eng');
        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $article->set('title', 'New translated article');
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $this->assertSame('New translated article', $article->get('title'));
        $this->assertSame('Content #1', $article->get('body'));

        $table->setLocale(null);
        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $this->assertSame('First Article', $article->get('title'));

        $table->setLocale('eng');
        $article->set('title', 'Wow, such translated article');
        $article->set('body', 'A translated body');
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $this->assertSame('Wow, such translated article', $article->get('title'));
        $this->assertSame('A translated body', $article->get('body'));
    }

    /**
     * Tests adding new translation to a record
     */
    public function testInsertNewTranslations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('fra');

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $article->set('title', 'Le titre');
        $table->save($article);
        $this->assertSame('fra', $article->get('_locale'));

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $this->assertSame('Le titre', $article->get('title'));
        $this->assertSame('First Article Body', $article->get('body'));

        $article->set('title', 'Un autre titre');
        $article->set('body', 'Le contenu');
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertSame('Un autre titre', $article->get('title'));
        $this->assertSame('Le contenu', $article->get('body'));
    }

    /**
     * Tests adding new translation to a record
     */
    public function testAllowEmptyFalse(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title'], 'allowEmptyTranslations' => false]);

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));

        $article = $table->patchEntity($article, [
            '_translations' => [
                'fra' => [
                    'title' => '',
                ],
            ],
        ]);

        $table->save($article);

        // Remove the Behavior to unset the content != '' condition
        $table->removeBehavior('Translate');

        $noFra = $table->I18n->find()->where(['locale' => 'fra'])->first();
        $this->assertEmpty($noFra);
    }

    /**
     * Tests adding new translation to a record with a missing translation
     */
    public function testAllowEmptyFalseWithNull(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'description'], 'allowEmptyTranslations' => false]);

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));

        $article = $table->patchEntity($article, [
            '_translations' => [
                'fra' => [
                    'title' => 'Title',
                ],
            ],
        ]);

        $table->save($article);

        // Remove the Behavior to unset the content != '' condition
        $table->removeBehavior('Translate');

        $fra = $table->I18n->find()->where(['locale' => 'fra'])->first();
        $this->assertNotEmpty($fra);
    }

    /**
     * Tests adding new translation to a record
     */
    public function testMixedAllowEmptyFalse(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body'], 'allowEmptyTranslations' => false]);

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));

        $article = $table->patchEntity($article, [
            '_translations' => [
                'fra' => [
                    'title' => '',
                    'body' => 'Bonjour',
                ],
            ],
        ]);

        $table->save($article);

        $fra = $table->I18n->find()
            ->where([
                'locale' => 'fra',
                'field' => 'body',
            ])
            ->first();
        $this->assertSame('Bonjour', $fra->content);

        // Remove the Behavior to unset the content != '' condition
        $table->removeBehavior('Translate');

        $noTitle = $table->I18n->find()
            ->where([
                'locale' => 'fra',
                'field' => 'title',
            ])
            ->first();
        $this->assertEmpty($noTitle);
    }

    /**
     * Tests adding new translation to a record
     */
    public function testMultipleAllowEmptyFalse(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body'], 'allowEmptyTranslations' => false]);

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));

        $article = $table->patchEntity($article, [
            '_translations' => [
                'fra' => [
                    'title' => '',
                    'body' => 'Bonjour',
                ],
                'de' => [
                    'title' => 'Titel',
                    'body' => 'Hallo',
                ],
            ],
        ]);

        $table->save($article);

        $fra = $table->I18n->find()
            ->where([
                'locale' => 'fra',
                'field' => 'body',
            ])
            ->first();
        $this->assertSame('Bonjour', $fra->content);

        $deTitle = $table->I18n->find()
            ->where([
                'locale' => 'de',
                'field' => 'title',
            ])
            ->first();
        $this->assertSame('Titel', $deTitle->content);

        $deBody = $table->I18n->find()
            ->where([
                'locale' => 'de',
                'field' => 'body',
            ])
            ->first();
        $this->assertSame('Hallo', $deBody->content);

        // Remove the Behavior to unset the content != '' condition
        $table->removeBehavior('Translate');

        $noTitle = $table->I18n->find()
            ->where([
                'locale' => 'fra',
                'field' => 'title',
            ])
            ->first();
        $this->assertEmpty($noTitle);
    }

    /**
     * Tests that it is possible to use the _locale property to specify the language
     * to use for saving an entity
     */
    public function testUpdateTranslationWithLocaleInEntity(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $article->set('_locale', 'fra');
        $article->set('title', 'Le titre');
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $this->assertSame('First Article', $article->get('title'));
        $this->assertSame('First Article Body', $article->get('body'));

        $table->setLocale('fra');
        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $this->assertSame('Le titre', $article->get('title'));
        $this->assertSame('First Article Body', $article->get('body'));
    }

    /**
     * Tests that translations are added to the whitelist of associations to be
     * saved
     */
    public function testSaveTranslationWithAssociationWhitelist(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setLocale('fra');

        $article = $table->find()->first();
        $this->assertSame(1, $article->get('id'));
        $article->set('title', 'Le titre');
        $table->save($article, ['associated' => ['Comments']]);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find()->first();
        $this->assertSame('Le titre', $article->get('title'));
    }

    /**
     * Tests that after deleting a translated entity, all translations are also removed
     */
    public function testDelete(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $article = $table->find()->first();
        $this->assertTrue($table->delete($article));

        $translations = $this->getTableLocator()->get('I18n')->find()
            ->where(['model' => 'Articles', 'foreign_key' => $article->id])
            ->count();
        $this->assertSame(0, $translations);
    }

    /**
     * Tests saving multiple translations at once when the translations already
     * exist in the database
     */
    public function testSaveMultipleTranslations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
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
        $this->assertSame('Another title', $translations['deu']->get('title'));
        $this->assertSame('Another body', $translations['eng']->get('body'));
    }

    /**
     * Tests saving multiple existing translations and adding new ones
     */
    public function testSaveMultipleNewTranslations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
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
        $this->assertSame('Another title', $translations['deu']->get('title'));
        $this->assertSame('Another body', $translations['eng']->get('body'));
        $this->assertSame('Titulo', $translations['spa']->get('title'));
        $this->assertSame('Titre', $translations['fre']->get('title'));
    }

    /**
     * Tests that iterating a resultset twice when using the translations finder
     * will not cause any errors nor information loss
     */
    public function testUseCountInFindTranslations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
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
     */
    public function testSavingWithNonDefaultLocale(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setEntityClass(TranslateArticle::class);
        I18n::setLocale('fra');
        $translations = [
            'fra' => ['title' => 'Un article'],
            'spa' => ['title' => 'Un artículo'],
        ];

        $article = $table->get(1);
        foreach ($translations as $lang => $data) {
            $article->translation($lang)->set($data, ['guard' => false]);
        }

        $table->save($article);
        $article = $table->find('translations')->where(['Articles.id' => 1])->first();
        $this->assertSame('Un article', $article->translation('fra')->title);
        $this->assertSame('Un artículo', $article->translation('spa')->title);
    }

    /**
     * Tests that translation queries are added to union queries as well.
     */
    public function testTranslationWithUnionQuery(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $table */
        $table->addBehavior('Translate', ['fields' => ['comment']]);
        $table->setLocale('spa');
        $query = $table->find()->where(['Comments.id' => 6]);
        $query2 = $table->find()->where(['Comments.id' => 5]);
        $query->union($query2);
        $results = $query->all()->sortBy('id', SORT_ASC)->toList();
        $this->assertCount(2, $results);

        $this->assertSame('First Comment for Second Article', $results[0]->comment);
        $this->assertSame('Second Comment for Second Article', $results[1]->comment);
    }

    /**
     * Tests the use of `referenceName` config option.
     */
    public function testAutoReferenceName(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $table->hasMany('OtherComments', ['className' => 'Comments']);
        $table->OtherComments->addBehavior(
            'Translate',
            ['fields' => ['comment']]
        );

        $items = $table->OtherComments->associations();
        $association = $items->getByProperty('comment_translation');
        $this->assertNotEmpty($association, 'Translation association not found');

        $found = false;
        foreach ($association->getConditions() as $key => $value) {
            if (strpos($key, 'comment_translation.model') !== false) {
                $found = true;
                $this->assertSame('Comments', $value);
                break;
            }
        }

        $this->assertTrue($found, '`referenceName` field condition on a Translation association was not found');
    }

    /**
     * Tests the use of unconventional `referenceName` config option.
     */
    public function testChangingReferenceName(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->setAlias('FavoritePost');
        $table->addBehavior(
            'Translate',
            ['fields' => ['body'], 'referenceName' => 'Posts']
        );

        $items = $table->associations();
        $association = $items->getByProperty('body_translation');
        $this->assertNotEmpty($association, 'Translation association not found');

        $found = false;
        foreach ($association->getConditions() as $key => $value) {
            if (strpos($key, 'body_translation.model') !== false) {
                $found = true;
                $this->assertSame('Posts', $value);
                break;
            }
        }

        $this->assertTrue($found, '`referenceName` field condition on a Translation association was not found');
    }

    /**
     * Tests that onlyTranslated will remove records from the result set
     * if they are not fully translated
     */
    public function testFilterUntranslated(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $table */
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'onlyTranslated' => true,
        ]);
        $table->setLocale('eng');
        $results = $table->find()->where(['Articles.id' => 1])->all();
        $this->assertCount(1, $results);

        $table->setLocale('fr');
        $results = $table->find()->where(['Articles.id' => 1])->all();
        $this->assertCount(0, $results);
    }

    /**
     * Tests that records not translated in the current locale will not be
     * present in the results for the translations finder, and also proves
     * that this can be overridden.
     */
    public function testFilterUntranslatedWithFinder(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $table */
        $table->addBehavior('Translate', [
            'fields' => ['comment'],
            'onlyTranslated' => true,
        ]);
        $table->setLocale('eng');
        $results = $table->find('translations')->all();
        $this->assertCount(4, $results);

        $table->setLocale('spa');
        $results = $table->find('translations')->all();
        $this->assertCount(1, $results);

        $table->setLocale('spa');
        $results = $table->find('translations', ['filterByCurrentLocale' => false])->all();
        $this->assertCount(6, $results);

        $table->setLocale('spa');
        $results = $table->find('translations')->all();
        $this->assertCount(1, $results);
    }

    /**
     * Tests that allowEmptyTranslations takes effect
     */
    public function testEmptyTranslations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        /** @var \Cake\ORM\Table|\Cake\ORM\Behavior\TranslateBehavior $table */
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body', 'description'],
            'allowEmptyTranslations' => false,
        ]);
        $table->setLocale('spa');
        $result = $table->find()->first();
        $this->assertNull($result->description);
    }

    /**
     * Test save with clean translate fields
     */
    public function testSaveWithCleanFields(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title']]);
        $table->setEntityClass(TranslateArticle::class);
        I18n::setLocale('fra');
        $article = $table->get(1);
        $article->set('body', 'New Body');
        $table->save($article);
        $result = $table->get(1);
        $this->assertSame('New Body', $result->body);
        $this->assertSame($article->title, $result->title);
    }

    /**
     * Test save new entity with _translations field
     */
    public function testSaveNewRecordWithTranslatesField(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->getValidator()->add('title', 'notBlank', ['rule' => 'notBlank']);
        $table->addBehavior('Translate', [
            'fields' => ['title'],
        ]);
        $table->setEntityClass(TranslateArticle::class);

        $data = [
            'author_id' => 1,
            'published' => 'N',
            '_translations' => [
                'en' => [
                    'title' => 'Title EN',
                    'body' => 'Body EN',
                ],
                'es' => [
                    'title' => 'Title ES',
                ],
            ],
        ];

        $article = $table->patchEntity($table->newEmptyEntity(), $data);
        $result = $table->save($article);

        $this->assertNotFalse($result);

        $expected = [
            [
                'en' => [
                    'title' => 'Title EN',
                    'locale' => 'en',
                ],
                'es' => [
                    'title' => 'Title ES',
                    'locale' => 'es',
                ],
            ],
        ];
        $result = $table->find('translations')->where(['id' => $result->id]);
        $this->assertEquals($expected, $this->_extractTranslations($result)->toArray());
    }

    /**
     * Tests adding new translation to a record where the only field is the translated one and it's not the default locale
     */
    public function testSaveNewRecordWithOnlyTranslationsNotDefaultLocale(): void
    {
        $table = $this->getTableLocator()->get('Sections');
        $table->getValidator()->add('title', 'notBlank', ['rule' => 'notBlank']);
        $table->addBehavior('Translate', [
            'fields' => ['title'],
        ]);

        $data = [
            '_translations' => [
                'es' => [
                    'title' => 'Title ES',
                ],
            ],
        ];

        $group = $table->newEntity($data);
        $result = $table->save($group);
        $this->assertNotFalse($result, 'Record should save.');

        $expected = [
            [
                'es' => [
                    'title' => 'Title ES',
                    'locale' => 'es',
                ],
            ],
        ];
        $result = $table->find('translations')->where(['id' => $result->id]);
        $this->assertEquals($expected, $this->_extractTranslations($result)->toArray());
    }

    /**
     * Test that existing records can be updated when only translations
     * are modified/dirty.
     */
    public function testSaveExistingRecordOnlyTranslations(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setEntityClass(TranslateArticle::class);

        $data = [
            '_translations' => [
                'es' => [
                    'title' => 'Spanish Translation',
                ],
            ],
        ];

        $article = $table->find()->first();
        $article = $table->patchEntity($article, $data);

        $this->assertNotFalse($table->save($article));

        $results = $this->_extractTranslations(
            $table->find('translations')->where(['id' => 1])
        )->first();

        $this->assertArrayHasKey('es', $results, 'New translation added');
        $this->assertArrayHasKey('eng', $results, 'Old translations present');
        $this->assertSame('Spanish Translation', $results['es']['title']);
    }

    /**
     * Test update entity with _translations field.
     */
    public function testSaveExistingRecordWithTranslatesField(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->setEntityClass(TranslateArticle::class);

        $data = [
            'author_id' => 1,
            'published' => 'Y',
            '_translations' => [
                'eng' => [
                    'title' => 'First Article1',
                    'body' => 'First Article content has been updated',
                ],
                'spa' => [
                    'title' => 'Mi nuevo titulo',
                    'body' => 'Contenido Actualizado',
                ],
            ],
        ];

        $article = $table->find()->first();
        $article = $table->patchEntity($article, $data);

        $this->assertNotFalse($table->save($article));

        $results = $this->_extractTranslations(
            $table->find('translations')->where(['id' => 1])
        )->first();

        $this->assertSame('Mi nuevo titulo', $results['spa']['title']);
        $this->assertSame('Contenido Actualizado', $results['spa']['body']);

        $this->assertSame('First Article1', $results['eng']['title']);
        $this->assertSame('Description #1', $results['eng']['description']);
    }

    /**
     * Tests that default locale saves ok.
     */
    public function testSaveDefaultLocale(): void
    {
        $table = $this->getTableLocator()->get('Articles');
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
        $this->assertSame('New title', $article->get('title'));
        $this->assertSame('New body', $article->get('body'));
    }

    /**
     * Test that when `defaultLocale` feature is disabled translations table
     * is always used.
     */
    public function testSaveDefaultLocaleFalse(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', [
            'defaultLocale' => '',
            'fields' => ['title', 'body'],
        ]);

        $data = [
            'title' => 'New title',
            'body' => 'New body',
            'published' => 'Y',
        ];
        $article = $table->newEntity($data);
        $result = $table->save($article);
        $this->assertNotEmpty($result);

        $record = $table->get($article->id);
        $this->assertSame($data['title'], $record->title);
        $this->assertSame($data['body'], $record->body);

        $table->removeBehavior('Translate');
        $record = $table->get($article->id);
        $this->assertEmpty($record->title);
        $this->assertEmpty($record->body);

        $article->title = 'updated title';
        $table->addBehavior('Translate', [
            'defaultLocale' => '',
            'fields' => ['title', 'body'],
        ]);
        $result = $table->save($article);
        $this->assertNotEmpty($result);

        $record = $table->get($article->id);
        $this->assertSame('updated title', $record->title);

        $table->removeBehavior('Translate');
        $record = $table->get($article->id);
        $this->assertEmpty($record->title);
    }

    /**
     * Tests that translations are added to the whitelist of associations to be
     * saved
     */
    public function testSaveTranslationDefaultLocale(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $article = $table->get(1);
        $data = [
            'title' => 'New title',
            'body' => 'New body',
            '_translations' => [
                'es' => [
                    'title' => 'ES title',
                    'body' => 'ES body',
                ],
            ],
        ];
        $article = $table->patchEntity($article, $data);
        $table->save($article);
        $this->assertNull($article->get('_i18n'));

        $article = $table->find('translations')->where(['id' => 1])->first();
        $this->assertSame('New title', $article->get('title'));
        $this->assertSame('New body', $article->get('body'));

        $this->assertSame('ES title', $article->_translations['es']->title);
        $this->assertSame('ES body', $article->_translations['es']->body);
    }

    /**
     * Test that no properties are enabled when the translations
     * option is off.
     */
    public function testBuildMarshalMapTranslationsOff(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);

        $marshaller = $table->marshaller();
        $translate = $table->behaviors()->get('Translate');
        $result = $translate->buildMarshalMap($marshaller, [], ['translations' => false]);
        $this->assertSame([], $result);
    }

    /**
     * Test building a marshal map with translations on.
     */
    public function testBuildMarshalMapTranslationsOn(): void
    {
        $table = $this->getTableLocator()->get('Articles');
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
     */
    public function testBuildMarshalMapNonArrayData(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $translate = $table->behaviors()->get('Translate');

        $map = $translate->buildMarshalMap($table->marshaller(), [], []);
        $entity = $table->newEmptyEntity();
        $result = $map['_translations']('garbage', $entity);
        $this->assertNull($result, 'Non-array should not error out.');
        $this->assertEmpty($entity->getErrors());
        $this->assertEmpty($entity->get('_translations'));
    }

    /**
     * Test buildMarshalMap() builds new entities.
     */
    public function testBuildMarshalMapBuildEntities(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $translate = $table->behaviors()->get('Translate');

        $map = $translate->buildMarshalMap($table->marshaller(), [], []);
        $entity = $table->newEmptyEntity();
        $data = [
            'en' => [
                'title' => 'English Title',
                'body' => 'English Content',
            ],
            'es' => [
                'title' => 'Titulo Español',
                'body' => 'Contenido Español',
            ],
        ];
        $result = $map['_translations']($data, $entity);
        $this->assertEmpty($entity->getErrors(), 'No validation errors.');
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('es', $result);
        $this->assertSame('English Title', $result['en']->title);
        $this->assertSame('Titulo Español', $result['es']->title);
    }

    /**
     * Test that validation errors are added to the original entity.
     */
    public function testBuildMarshalMapBuildEntitiesValidationErrors(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'validator' => 'custom',
        ]);
        $validator = (new Validator())->notEmptyString('title');
        $table->setValidator('custom', $validator);
        $translate = $table->behaviors()->get('Translate');

        $entity = $table->newEmptyEntity();
        $map = $translate->buildMarshalMap($table->marshaller(), [], []);
        $data = [
            'en' => [
                'title' => 'English Title',
                'body' => 'English Content',
            ],
            'es' => [
                'title' => '',
                'body' => 'Contenido Español',
            ],
        ];
        $result = $map['_translations']($data, $entity);
        $this->assertNotEmpty($entity->getErrors(), 'Needs validation errors.');
        $expected = [
            'title' => [
                '_empty' => 'This field cannot be left empty',
            ],
        ];
        $this->assertEquals($expected, $entity->getError('_translations.es'));

        $this->assertSame('English Title', $result['en']->title);
        $this->assertNull($result['es']->title);
    }

    /**
     * Test that marshalling updates existing translation entities.
     */
    public function testBuildMarshalMapUpdateExistingEntities(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
        ]);
        $translate = $table->behaviors()->get('Translate');

        $entity = $table->newEmptyEntity();
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
            ],
        ];
        $result = $map['_translations']($data, $entity);
        $this->assertEmpty($entity->getErrors(), 'No validation errors.');
        $this->assertSame($en, $result['en']);
        $this->assertSame($es, $result['es']);
        $this->assertSame($en, $entity->get('_translations')['en']);
        $this->assertSame($es, $entity->get('_translations')['es']);

        $this->assertSame('English Title', $result['en']->title);
        $this->assertSame('Spanish Title', $result['es']->title);
        $this->assertSame('Old body', $result['en']->body);
        $this->assertSame('Old body', $result['es']->body);
    }

    /**
     * Test that updating translation records works with validations.
     */
    public function testBuildMarshalMapUpdateEntitiesValidationErrors(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'validator' => 'custom',
        ]);
        $validator = (new Validator())->notEmptyString('title');
        $table->setValidator('custom', $validator);
        $translate = $table->behaviors()->get('Translate');

        $entity = $table->newEmptyEntity();
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
                'body' => 'English Content',
            ],
            'es' => [
                'title' => '',
                'body' => 'Contenido Español',
            ],
        ];
        $map['_translations']($data, $entity);
        $this->assertNotEmpty($entity->getErrors(), 'Needs validation errors.');
        $expected = [
            'title' => [
                '_empty' => 'This field cannot be left empty',
            ],
        ];
        $this->assertEquals($expected, $entity->getError('_translations.es'));
    }

    /**
     * Test that the behavior uses associations' locator.
     */
    public function testDefaultTableLocator(): void
    {
        $locator = new TableLocator();

        $table = $locator->get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'validator' => 'custom',
        ]);

        $behaviorLocator = $table->behaviors()->get('Translate')->getTableLocator();

        $this->assertSame($locator, $behaviorLocator);
        $this->assertSame($table->associations()->getTableLocator(), $behaviorLocator);
        $this->assertNotSame($this->getTableLocator(), $behaviorLocator);
    }

    /**
     * Test that the behavior uses a custom locator.
     */
    public function testCustomTableLocator(): void
    {
        $locator = new TableLocator();

        $table = $this->getTableLocator()->get('Articles');
        $table->addBehavior('Translate', [
            'fields' => ['title', 'body'],
            'validator' => 'custom',
            'tableLocator' => $locator,
        ]);

        $behaviorLocator = $table->behaviors()->get('Translate')->getTableLocator();

        $this->assertSame($locator, $behaviorLocator);
        $this->assertNotSame($table->associations()->getTableLocator(), $behaviorLocator);
        $this->assertNotSame($this->getTableLocator(), $behaviorLocator);
    }

    /**
     * Tests that using deep matching doesn't cause an association property to be created.
     */
    public function testDeepMatchingDoesNotCreateAssociationProperty(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->Comments->belongsTo('Authors')->setForeignKey('user_id');

        $table->Comments->addBehavior('Translate', ['fields' => ['comment']]);
        $table->Comments->setLocale('abc');

        $table->Comments->Authors->addBehavior('Translate', ['fields' => ['name']]);
        $table->Comments->Authors->setLocale('xyz');

        $this->assertNotEquals($table->Comments->getLocale(), I18n::getLocale());
        $this->assertNotEquals($table->Comments->Authors->getLocale(), I18n::getLocale());

        $result = $table
            ->find()
            ->contain('Comments')
            ->matching('Comments.Authors')
            ->first();

        $this->assertArrayNotHasKey('author', $result->comments);
    }

    /**
     * Tests that the _locale property is set on the entity in the _matchingData property.
     */
    public function testLocalePropertyIsSetInMatchingData(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');

        $table->Comments->addBehavior('Translate', ['fields' => ['comment']]);
        $table->Comments->setLocale('abc');

        $this->assertNotEquals($table->Comments->getLocale(), I18n::getLocale());

        $result = $table
            ->find()
            ->contain('Comments')
            ->matching('Comments')
            ->first();

        $this->assertArrayNotHasKey('_locale', $result->comments);
        $this->assertSame('abc', $result->_matchingData['Comments']->_locale);
    }

    /**
     * Tests that the _locale property is set on the entity in the _matchingData property
     * when using deep matching.
     */
    public function testLocalePropertyIsSetInMatchingDataWhenUsingDeepMatching(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments');
        $table->Comments->belongsTo('Authors')->setForeignKey('user_id');

        $table->Comments->addBehavior('Translate', ['fields' => ['comment']]);
        $table->Comments->setLocale('abc');

        $table->Comments->Authors->addBehavior('Translate', ['fields' => ['name']]);
        $table->Comments->Authors->setLocale('xyz');

        $this->assertNotEquals($table->Comments->getLocale(), I18n::getLocale());
        $this->assertNotEquals($table->Comments->Authors->getLocale(), I18n::getLocale());

        $result = $table
            ->find()
            ->contain('Comments.Authors')
            ->matching('Comments.Authors')
            ->first();

        $this->assertArrayNotHasKey('_locale', $result->comments);
        $this->assertSame('abc', $result->_matchingData['Comments']->_locale);
        $this->assertSame('xyz', $result->_matchingData['Authors']->_locale);
    }

    /**
     * Tests that the _locale property is set on the entity in the _matchingData property
     * when using contained matching.
     */
    public function testLocalePropertyIsSetInMatchingDataWhenUsingContainedMatching(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany('Articles');
        $table->Articles->belongsToMany('Tags');

        $table->Articles->addBehavior('Translate', ['fields' => ['title', 'body']]);
        $table->Articles->setLocale('abc');

        $table->Articles->Tags->addBehavior('Translate', ['fields' => ['name']]);
        $table->Articles->Tags->setLocale('xyz');

        $this->assertNotEquals($table->Articles->getLocale(), I18n::getLocale());
        $this->assertNotEquals($table->Articles->Tags->getLocale(), I18n::getLocale());

        $result = $table
            ->find()
            ->contain([
                'Articles' => function ($query) {
                    return $query->matching('Tags');
                },
                'Articles.Tags',
            ])
            ->first();

        $this->assertArrayNotHasKey('_locale', $result->articles);
        $this->assertArrayNotHasKey('_locale', $result->articles[0]->tags);
        $this->assertSame('abc', $result->articles[0]->_locale);
        $this->assertSame('xyz', $result->articles[0]->_matchingData['Tags']->_locale);
    }
}
