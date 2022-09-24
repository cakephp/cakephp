<?php
declare(strict_types=1);

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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Validation\Validator;

/**
 * Description of ValidationBehavior
 */
class ValidationBehavior extends Behavior
{
    /**
     * @return $this
     */
    public function validationBehavior(Validator $validator)
    {
        return $validator->add('name', 'behaviorRule');
    }
}
