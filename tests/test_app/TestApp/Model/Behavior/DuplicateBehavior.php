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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Behavior;

use Cake\ORM\Behavior;

/**
 * Test class for trigging duplicate method errors.
 */
class DuplicateBehavior extends Behavior
{

    protected $_defaultConfig = [
        'implementedFinders' => [
            'children' => 'findChildren',
        ],
        'implementedMethods' => [
            'slugify' => 'slugify',
        ]
    ];

    public function findChildren()
    {
    }

    public function slugify()
    {
    }
}
