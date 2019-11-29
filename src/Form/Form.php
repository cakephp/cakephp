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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Form;

use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Utility\Hash;
use Cake\Validation\ValidatorAwareInterface;
use Cake\Validation\ValidatorAwareTrait;

/**
 * Form abstraction used to create forms not tied to ORM backed models,
 * or to other permanent datastores. Ideal for implementing forms on top of
 * API services, or contact forms.
 *
 * ### Building a form
 *
 * This class is most useful when subclassed. In a subclass you
 * should define the `_buildSchema`, `validationDefault` and optionally,
 * the `_execute` methods. These allow you to declare your form's
 * fields, validation and primary action respectively.
 *
 * Forms are conventionally placed in the `App\Form` namespace.
 */
class Form implements EventListenerInterface, EventDispatcherInterface, ValidatorAwareInterface
{
    use EventDispatcherTrait;
    use ValidatorAwareTrait;

    /**
     * Name of default validation set.
     *
     * @var string
     */
    public const DEFAULT_VALIDATOR = 'default';

    /**
     * The alias this object is assigned to validators as.
     *
     * @var string
     */
    public const VALIDATOR_PROVIDER_NAME = 'form';

    /**
     * The name of the event dispatched when a validator has been built.
     *
     * @var string
     */
    public const BUILD_VALIDATOR_EVENT = 'Form.buildValidator';

    /**
     * Schema class.
     *
     * @var string
     */
    protected $_schemaClass = Schema::class;

    /**
     * The schema used by this form.
     *
     * @var \Cake\Form\Schema
     */
    protected $_schema;

    /**
     * The errors if any
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * Form's data.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Constructor
     *
     * @param \Cake\Event\EventManager|null $eventManager The event manager.
     *  Defaults to a new instance.
     */
    public function __construct(?EventManager $eventManager = null)
    {
        if ($eventManager !== null) {
            $this->setEventManager($eventManager);
        }

        $this->getEventManager()->on($this);

        if (method_exists($this, '_buildValidator')) {
            deprecationWarning(
                static::class . ' implements `_buildValidator` which is no longer used. ' .
                'You should implement `buildValidator(Validator $validator, string $name): void` ' .
                'or `validationDefault(Validator $validator): Validator` instead.'
            );
        }
    }

    /**
     * Get the Form callbacks this form is interested in.
     *
     * The conventional method map is:
     *
     * - Form.buildValidator => buildValidator
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        if (method_exists($this, 'buildValidator')) {
            return [
                self::BUILD_VALIDATOR_EVENT => 'buildValidator',
            ];
        }

        return [];
    }

    /**
     * Get/Set the schema for this form.
     *
     * This method will call `_buildSchema()` when the schema
     * is first built. This hook method lets you configure the
     * schema or load a pre-defined one.
     *
     * @param \Cake\Form\Schema|null $schema The schema to set, or null.
     * @return \Cake\Form\Schema the schema instance.
     */
    public function schema(?Schema $schema = null): Schema
    {
        if ($schema === null && empty($this->_schema)) {
            $schema = $this->_buildSchema(new $this->_schemaClass());
        }
        if ($schema) {
            $this->_schema = $schema;
        }

        return $this->_schema;
    }

    /**
     * A hook method intended to be implemented by subclasses.
     *
     * You can use this method to define the schema using
     * the methods on Cake\Form\Schema, or loads a pre-defined
     * schema from a concrete class.
     *
     * @param \Cake\Form\Schema $schema The schema to customize.
     * @return \Cake\Form\Schema The schema to use.
     */
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema;
    }

    /**
     * Used to check if $data passes this form's validation.
     *
     * @param array $data The data to check.
     * @return bool Whether or not the data is valid.
     */
    public function validate(array $data): bool
    {
        $validator = $this->getValidator();
        $this->_errors = $validator->validate($data);

        return count($this->_errors) === 0;
    }

    /**
     * Get the errors in the form
     *
     * Will return the errors from the last call
     * to `validate()` or `execute()`.
     *
     * @return array Last set validation errors.
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Set the errors in the form.
     *
     * ```
     * $errors = [
     *      'field_name' => ['rule_name' => 'message']
     * ];
     *
     * $form->setErrors($errors);
     * ```
     *
     * @param array $errors Errors list.
     * @return $this
     */
    public function setErrors(array $errors)
    {
        $this->_errors = $errors;

        return $this;
    }

    /**
     * Execute the form if it is valid.
     *
     * First validates the form, then calls the `_execute()` hook method.
     * This hook method can be implemented in subclasses to perform
     * the action of the form. This may be sending email, interacting
     * with a remote API, or anything else you may need.
     *
     * @param array $data Form data.
     * @return bool False on validation failure, otherwise returns the
     *   result of the `_execute()` method.
     */
    public function execute(array $data): bool
    {
        if (!$this->validate($data)) {
            return false;
        }

        return $this->_execute($data);
    }

    /**
     * Hook method to be implemented in subclasses.
     *
     * Used by `execute()` to execute the form's action.
     *
     * @param array $data Form data.
     * @return bool
     */
    protected function _execute(array $data): bool
    {
        return true;
    }

    /**
     * Get field data.
     *
     * @param string|null $field The field name or null to get data array with
     *   all fields.
     * @return mixed
     */
    public function getData(?string $field = null)
    {
        if ($field === null) {
            return $this->_data;
        }

        return Hash::get($this->_data, $field);
    }

    /**
     * Set form data.
     *
     * @param array $data Data array.
     * @return $this
     */
    public function setData(array $data)
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * Get the printable version of a Form instance.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        $special = [
            '_schema' => $this->schema()->__debugInfo(),
            '_errors' => $this->getErrors(),
            '_validator' => $this->getValidator()->__debugInfo(),
        ];

        return $special + get_object_vars($this);
    }
}
