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
 * @since         3.0.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

use Cake\Event\EventDispatcherInterface;

/**
 * A trait that provides methods for building and
 * interacting with Validators.
 *
 * This trait is useful when building ORM like features where
 * the implementing class wants to build and customize a variety
 * of validator instances.
 *
 * This trait expects that classes including it define two constants:
 *
 * - `DEFAULT_VALIDATOR` - The default validator name.
 * - `VALIDATOR_PROVIDER_NAME ` - The provider name the including class is assigned
 *   in validators.
 *
 * If the including class also implements events the `Model.buildValidator` event
 * will be triggered when validators are created.
 */
trait ValidatorAwareTrait
{

    /**
     * Validator class.
     *
     * @var string
     */
    protected $_validatorClass = '\Cake\Validation\Validator';

    /**
     * A list of validation objects indexed by name
     *
     * @var array
     */
    protected $_validators = [];

    /**
     * Returns the validation rules tagged with $name. It is possible to have
     * multiple different named validation sets, this is useful when you need
     * to use varying rules when saving from different routines in your system.
     *
     * There are two different ways of creating and naming validation sets: by
     * creating a new method inside your own Table subclass, or by building
     * the validator object yourself and storing it using this method.
     *
     * For example, if you wish to create a validation set called 'forSubscription',
     * you will need to create a method in your Table subclass as follows:
     *
     * ```
     * public function validationForSubscription($validator)
     * {
     *  return $validator
     *  ->add('email', 'valid-email', ['rule' => 'email'])
     *  ->add('password', 'valid', ['rule' => 'notBlank'])
     *  ->requirePresence('username');
     * }
     * ```
     *
     * Otherwise, you can build the object by yourself and store it in the Table object:
     *
     * ```
     * $validator = new \Cake\Validation\Validator($table);
     * $validator
     *  ->add('email', 'valid-email', ['rule' => 'email'])
     *  ->add('password', 'valid', ['rule' => 'notBlank'])
     *  ->allowEmpty('bio');
     * $table->validator('forSubscription', $validator);
     * ```
     *
     * You can implement the method in `validationDefault` in your Table subclass
     * should you wish to have a validation set that applies in cases where no other
     * set is specified.
     *
     * @param string|null $name the name of the validation set to return
     * @param \Cake\Validation\Validator|null $validator The validator instance to store,
     *   use null to get a validator.
     * @return \Cake\Validation\Validator
     */
    public function validator($name = null, Validator $validator = null)
    {
        if ($name === null) {
            $name = self::DEFAULT_VALIDATOR;
        }
        if ($validator === null && isset($this->_validators[$name])) {
            return $this->_validators[$name];
        }

        if ($validator === null) {
            $validator = new $this->_validatorClass;
            $validator = $this->{'validation' . ucfirst($name)}($validator);
            if ($this instanceof EventDispatcherInterface) {
                $this->dispatchEvent('Model.buildValidator', compact('validator', 'name'));
            }
        }

        $validator->provider(self::VALIDATOR_PROVIDER_NAME, $this);
        return $this->_validators[$name] = $validator;
    }

    /**
     * Returns the default validator object. Subclasses can override this function
     * to add a default validation set to the validator object.
     *
     * @param \Cake\Validation\Validator $validator The validator that can be modified to
     * add some rules to it.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        return $validator;
    }
}
