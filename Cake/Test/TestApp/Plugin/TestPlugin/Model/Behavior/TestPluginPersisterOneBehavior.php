<?php
/**
 * Behavior for binding management.
 *
 * Behavior to simplify manipulating a model's bindings when doing a find operation
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
namespace TestPlugin\Model\Behavior;

use Cake\Model\ModelBehavior;

/**
 * Behavior to allow for dynamic and atomic manipulation of a Model's associations used for a find call. Most useful for limiting
 * the amount of associations and data returned.
 *
 */
class TestPluginPersisterOneBehavior extends ModelBehavior {
}
