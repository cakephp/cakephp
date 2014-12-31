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
namespace Cake\ORM;

use Cake\Validation\Validator;

/**
 * Contains a method that can be used to apply a validator to an entity's internal
 * properties. This trait can be used to satisfy the Cake\Validation\ValidatableInterface
 */
trait EntityValidatorTrait
{

    /**
     * Validates the internal properties using a validator object and returns any
     * validation errors found.
     *
     * @param \Cake\Validation\Validator $validator The validator to use when validating the entity.
     * @return array
     */
    public function validate(Validator $validator)
    {
        $data = $this->_properties;
        $new = $this->isNew();
        $validator->provider('entity', $this);
        $this->errors($validator->errors($data, $new === null ? true : $new));
        return $this->_errors;
    }
}
