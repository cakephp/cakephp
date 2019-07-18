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
 * @since         3.2.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

/**
 * Contains logic for invoking an application rule.
 *
 * Combined with Cake\Datasource\RuleChecker as an implementation
 * detail to de-duplicate rule decoration and provide cleaner separation
 * of duties.
 *
 * @internal
 */
class RuleInvoker
{
    /**
     * The rule name
     *
     * @var string
     */
    protected $name;

    /**
     * Rule options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Rule callable
     *
     * @var callable
     */
    protected $rule;

    /**
     * Constructor
     *
     * ### Options
     *
     * - `errorField` The field errors should be set onto.
     * - `message` The error message.
     *
     * Individual rules may have additional options that can be
     * set here. Any options will be passed into the rule as part of the
     * rule $scope.
     *
     * @param callable $rule The rule to be invoked.
     * @param string $name The name of the rule. Used in error messsages.
     * @param array $options The options for the rule. See above.
     */
    public function __construct(callable $rule, $name, array $options = [])
    {
        $this->rule = $rule;
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Set options for the rule invocation.
     *
     * Old options will be merged with the new ones.
     *
     * @param array $options The options to set.
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options + $this->options;

        return $this;
    }

    /**
     * Set the rule name.
     *
     * Only truthy names will be set.
     *
     * @param string $name The name to set.
     * @return $this
     */
    public function setName($name)
    {
        if ($name) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * Invoke the rule.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity the rule
     *   should apply to.
     * @param array $scope The rule's scope/options.
     * @return bool Whether or not the rule passed.
     */
    public function __invoke($entity, $scope)
    {
        $rule = $this->rule;
        $pass = $rule($entity, $this->options + $scope);
        if ($pass === true || empty($this->options['errorField'])) {
            return $pass === true;
        }

        $message = 'invalid';
        if (isset($this->options['message'])) {
            $message = $this->options['message'];
        }
        if (is_string($pass)) {
            $message = $pass;
        }
        if ($this->name) {
            $message = [$this->name => $message];
        } else {
            $message = [$message];
        }
        $errorField = $this->options['errorField'];
        $entity->setError($errorField, $message);

        if ($entity instanceof InvalidPropertyInterface && isset($entity->{$errorField})) {
            $invalidValue = $entity->{$errorField};
            $entity->setInvalidField($errorField, $invalidValue);
        }

        return $pass === true;
    }
}
