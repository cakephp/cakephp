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

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;
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
        $table = $this->getTableLocator()->get('NumberTrees');
        $table->setPrimaryKey(['id']);
        $table->addBehavior('Tree');
        $table->addBehavior('Translate', ['fields' => ['name']]);
        $table->setEntityClass(__NAMESPACE__ . '\\NumberTree');

        $all = $table->find('threaded')->find('translations');
        $results = [];
        foreach ($all as $node) {
            $results[] = $node->translation('dan')->name;
        }
        $this->assertEquals(['Elektroniker', 'Alien Tingerne'], $results);
    }
}
