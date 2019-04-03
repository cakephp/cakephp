<?php
/**
 * Behavior for binding management.
 *
 * Behavior to simplify manipulating a model's bindings when doing a find operation
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class SluggableBehavior extends Behavior
{

    public function beforeFind(Event $event, Query $query, $options = [])
    {
        $query->where(['slug' => 'test']);

        return $query;
    }

    public function findNoSlug(Query $query, $options = [])
    {
        $query->where(['slug' => null]);

        return $query;
    }

    public function slugify($value)
    {
        return Inflector::slug($value);
    }
}
