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
 * @since         3.0.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

use Cake\Event\EventDispatcherInterface;
use RuntimeException;

/**
 * A trait that provides methods for building and
 * interacting with Validators.
 *
 * This trait is useful when building ORM like features where
 * the implementing class wants to build and customize a variety
 * of validator instances.
 *
 * This trait expects that classes including it define three constants:
 *
 * - `DEFAULT_VALIDATOR` - The default validator name.
 * - `VALIDATOR_PROVIDER_NAME ` - The provider name the including class is assigned
 *   in validators.
 * - `BUILD_VALIDATOR_EVENT` - The name of the event to be triggred when validators
 *   are built.
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
     * @var \Cake\Validation\Validator[]
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
     * @throws \RuntimeException
     * @deprecated 3.5.0 Use getValidator/setValidator instead.
     */
    public function validator($name = null, Validator $validator = null)
    {
        if ($validator !== null) {
            $name = $name ?: self::DEFAULT_VALIDATOR;
            $this->setValidator($name, $validator);
        }

        return $this->getValidator($name);
    }

    /**
     * Returns the validation rules tagged with $name. It is possible to have
     * multiple different named validation sets, this is useful when you need
     * to use varying rules when saving from different routines in your system.
     *
     * If a validator has not been set earlier, this method will build a valiator
     * using a method inside your class.
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
     * $validator = $this->getValidator('forSubscription');
     * ```
     *
     * You can implement the method in `validationDefault` in your Table subclass
     * should you wish to have a validation set that applies in cases where no other
     * set is specified.
     *
     * If a $name argument has not been provided, the default validator will be returned.
     * You can configure your default validator name in a `DEFAULT_VALIDATOR`
     * class constant.
     *
     * @param string|null $name The name of the validation set to return.
     * @return \Cake\Validation\Validator
     */
    public function getValidator($name = null)
    {
        $name = $name ?: self::DEFAULT_VALIDATOR;
        if (!isset($this->_validators[$name])) {
            $validator = $this->createValidator($name);
            $this->setValidator($name, $validator);
        }

        return $this->_validators[$name];
    }

    /**
     * Creates a validator using a custom method inside your class.
     *
     * This method is used only to build a new validator and it does not store
     * it in your object. If you want to build and reuse validators,
     * use getValidator() method instead.
     *
     * @param string $name The name of the validation set to create.
     * @return \Cake\Validation\Validator
     * @throws \RuntimeException
     */
    protected function createValidator($name)
    {
        $method = 'validation' . ucfirst($name);
        if (!$this->validationMethodExists($method)) {
            $message = sprintf('The %s::%s() validation method does not exists.', __CLASS__, $method);
            throw new RuntimeException($message);
        }

        $validator = new $this->_validatorClass;
        $validator = $this->$method($validator);
        if ($this instanceof EventDispatcherInterface) {
            $event = defined(self::class . '::BUILD_VALIDATOR_EVENT') ? self::BUILD_VALIDATOR_EVENT : 'Model.buildValidator';
            $this->dispatchEvent($event, compact('validator', 'name'));
        }

        if (!$validator instanceof Validator) {
            throw new RuntimeException(sprintf('The %s::%s() validation method must return an instance of %s.', __CLASS__, $method, Validator::class));
        }

        return $validator;
    }

    /**
     * This method stores a custom validator under the given name.
     *
     * You can build the object by yourself and store it in your object:
     *
     * ```
     * $validator = new \Cake\Validation\Validator($table);
     * $validator
     *  ->add('email', 'valid-email', ['rule' => 'email'])
     *  ->add('password', 'valid', ['rule' => 'notBlank'])
     *  ->allowEmpty('bio');
     * $this->setValidator('forSubscription', $validator);
     * ```
     *
     * @param string $name The name of a validator to be set.
     * @param \Cake\Validation\Validator $validator Validator object to be set.
     * @return $this
     */
    public function setValidator($name, Validator $validator)
    {
        $validator->setProvider(self::VALIDATOR_PROVIDER_NAME, $this);
        $this->_validators[$name] = $validator;

        return $this;
    }

    /**
     * Checks whether or not a validator has been set.
     *
     * @param string $name The name of a validator.
     * @return bool
     */
    public function hasValidator($name)
    {
        $method = 'validation' . ucfirst($name);
        if ($this->validationMethodExists($method)) {
            return true;
        }

        return isset($this->_validators[$name]);
    }

    /**
     * Checks if validation method exists.
     *
     * @param string $name Validation method name.
     * @return bool
     */
    protected function validationMethodExists($name)
    {
        return method_exists($this, $name);
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
