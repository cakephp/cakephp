<?php
declare(strict_types=1);

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
use InvalidArgumentException;

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
 * - `BUILD_VALIDATOR_EVENT` - The name of the event to be triggered when validators
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
    protected string $_validatorClass = Validator::class;

    /**
     * A list of validation objects indexed by name
     *
     * @var array<\Cake\Validation\Validator>
     */
    protected array $_validators = [];

    /**
     * Returns the validation rules tagged with $name. It is possible to have
     * multiple different named validation sets, this is useful when you need
     * to use varying rules when saving from different routines in your system.
     *
     * If a validator has not been set earlier, this method will build a validator
     * using a method inside your class.
     *
     * For example, if you wish to create a validation set called 'forSubscription',
     * you will need to create a method in your Table subclass as follows:
     *
     * ```
     * public function validationForSubscription($validator)
     * {
     *     return $validator
     *         ->add('email', 'valid-email', ['rule' => 'email'])
     *         ->add('password', 'valid', ['rule' => 'notBlank'])
     *         ->requirePresence('username');
     * }
     *
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
    public function getValidator(?string $name = null): Validator
    {
        $name = $name ?: static::DEFAULT_VALIDATOR;
        if (!isset($this->_validators[$name])) {
            $this->setValidator($name, $this->createValidator($name));
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
     * @throws \InvalidArgumentException
     */
    protected function createValidator(string $name): Validator
    {
        $method = 'validation' . ucfirst($name);
        if (!$this->validationMethodExists($method)) {
            $message = sprintf('The `%s::%s()` validation method does not exists.', static::class, $method);
            throw new InvalidArgumentException($message);
        }

        $validator = new $this->_validatorClass();
        $validator = $this->$method($validator);
        if ($this instanceof EventDispatcherInterface) {
            $event = defined(static::class . '::BUILD_VALIDATOR_EVENT')
                ? static::BUILD_VALIDATOR_EVENT
                : 'Model.buildValidator';
            $this->dispatchEvent($event, compact('validator', 'name'));
        }

        assert(
            $validator instanceof Validator,
            sprintf(
                'The `%s::%s()` validation method must return an instance of `%s`.',
                static::class,
                $method,
                Validator::class,
            ),
        );

        return $validator;
    }

    /**
     * This method stores a custom validator under the given name.
     *
     * You can build the object by yourself and store it in your object:
     *
     * ```
     * $validator = new \Cake\Validation\Validator();
     * $validator
     *     ->add('email', 'valid-email', ['rule' => 'email'])
     *     ->add('password', 'valid', ['rule' => 'notBlank'])
     *     ->allowEmpty('bio');
     * $this->setValidator('forSubscription', $validator);
     * ```
     *
     * @param string $name The name of a validator to be set.
     * @param \Cake\Validation\Validator $validator Validator object to be set.
     * @return $this
     */
    public function setValidator(string $name, Validator $validator)
    {
        $validator->setProvider(static::VALIDATOR_PROVIDER_NAME, $this);
        $this->_validators[$name] = $validator;

        return $this;
    }

    /**
     * Checks whether a validator has been set.
     *
     * @param string $name The name of a validator.
     * @return bool
     */
    public function hasValidator(string $name): bool
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
    protected function validationMethodExists(string $name): bool
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
    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }
}
