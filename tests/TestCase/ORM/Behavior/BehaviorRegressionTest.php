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

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Stub entity class
 */
class NumberTree extends Entity
{

    use TranslateTrait;
}

/**
 * Behavior regression tests
 */
class BehaviorRegressionTest extends TestCase
{
    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.number_trees',
        'core.translates'
    ];

    /**
     * Tests that the tree behavior and the translations behavior play together
     *
     * @see https://github.com/cakephp/cakephp/issues/5982
     * @return void
     */
    public function testTreeAndTranslateIntegration()
    {
        $table = TableRegistry::get('NumberTrees');
        $table->primaryKey(['id']);
        $table->addBehavior('Tree');
        $table->addBehavior('Translate', ['fields' => ['name']]);
        $table->entityClass(__NAMESPACE__ . '\\NumberTree');

        $all = $table->find('threaded')->find('translations');
        $results = [];
        foreach ($all as $node) {
            $results[] = $node->translation('dan')->name;
        }
        $this->assertEquals(['Elektroniker', 'Alien Tingerne'], $results);
    }
}
