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
namespace Cake\Test\TestCase\ORM\Behavior\Translate;

use Cake\ORM\TableRegistry;
use Cake\Test\TestCase\ORM\Behavior\TranslateBehaviorTest;

/**
 * Translate behavior test case
 */
class ShadowTranslateEngineTest extends TranslateBehaviorTest
{

    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.translates',
        'core.articles',
        'core.comments',
        'core.authors',
        'core.ArticlesTranslations',
        'core.ArticlesMoreTranslations',
        'core.AuthorsTranslations',
        'core.CommentsTranslations'
    ];

    /**
     * Default behavior settings for all tests.
     *
     * @var array
     */
    protected $_behaviorDefaults = [
        'engine' => '\Cake\ORM\Behavior\Translate\ShadowTableEngine',
    ];

    /**
     * Tests the use of `model` config option.
     *
     * Overloading the inherited method with an empty body because this engine does not use the model option.
     *
     * @return void
     */
    public function testChangingModelFieldValue()
    {
    }

    /**
     * Tests that after deleting a translated entity, all translations are also removed.
     *
     * @return void
     */
    public function testDelete()
    {
        $table = TableRegistry::get('Articles');
        $this->_addBehavior($table, ['fields' => ['title', 'body']]);
        $article = $table->find()->first();
        $this->assertTrue($table->delete($article));

        $translations = TableRegistry::get('ArticlesTranslations')->find()
            ->where(['id' => 1])
            ->count();
        $this->assertEquals(0, $translations);
    }

    /**
     * testFindTranslations
     *
     * The parent test expects description translations in only some of the records
     * that's incompatible with the shadow-translate behavior, since the schema
     * dictates what fields to expect to be translated and doesnt permit any EAV
     * style translations.
     *
     * @return void
     */
    public function testFindTranslations() {
        $this->markTestSkipped();
    }

    /**
     * testConditions
     *
     * The parent test applies conditions to the translation table; the tested
     * example is `content <> ''`. This is not necessary/inappropriate for
     * the shadow translate behavior, as any conditions would apply to the
     * translation record as a whole, and not a single translated field's value.
     *
     * @return void.
     */
    public function testConditions() {
        $this->markTestSkipped();
    }
}
