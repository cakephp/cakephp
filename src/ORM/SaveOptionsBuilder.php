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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use ArrayObject;
use RuntimeException;

/**
 * OOP style Save Option Builder.
 *
 * This allows you to build options to save entities in a OOP style and helps
 * you to avoid mistakes by validating the options as you build them.
 *
 * @see \Cake\Datasource\RulesChecker
 */
class SaveOptionsBuilder extends ArrayObject
{
    use AssociationsNormalizerTrait;

    /**
     * Options
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Table object.
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Table $table A table instance.
     * @param array $options Options to parse when instantiating.
     */
    public function __construct(Table $table, array $options = [])
    {
        $this->_table = $table;
        $this->parseArrayOptions($options);

        parent::__construct();
    }

    /**
     * Takes an options array and populates the option object with the data.
     *
     * This can be used to turn an options array into the object.
     *
     * @throws \InvalidArgumentException If a given option key does not exist.
     * @param array $array Options array.
     * @return $this
     */
    public function parseArrayOptions(array $array)
    {
        foreach ($array as $key => $value) {
            $this->{$key}($value);
        }

        return $this;
    }

    /**
     * Set associated options.
     *
     * @param string|array $associated String or array of associations.
     * @return $this
     */
    public function associated($associated)
    {
        $associated = $this->_normalizeAssociations($associated);
        $this->_associated($this->_table, $associated);
        $this->_options['associated'] = $associated;

        return $this;
    }

    /**
     * Checks that the associations exists recursively.
     *
     * @param \Cake\ORM\Table $table Table object.
     * @param array $associations An associations array.
     * @return void
     */
    protected function _associated(Table $table, array $associations): void
    {
        foreach ($associations as $key => $associated) {
            if (is_int($key)) {
                $this->_checkAssociation($table, $associated);
                continue;
            }
            $this->_checkAssociation($table, $key);
            if (isset($associated['associated'])) {
                $this->_associated($table->getAssociation($key)->getTarget(), $associated['associated']);
                continue;
            }
        }
    }

    /**
     * Checks if an association exists.
     *
     * @throws \RuntimeException If no such association exists for the given table.
     * @param \Cake\ORM\Table $table Table object.
     * @param string $association Association name.
     * @return void
     */
    protected function _checkAssociation(Table $table, string $association): void
    {
        if (!$table->associations()->has($association)) {
            throw new RuntimeException(sprintf(
                'Table `%s` is not associated with `%s`',
                get_class($table),
                $association
            ));
        }
    }

    /**
     * Set the guard option.
     *
     * @param bool $guard Guard the properties or not.
     * @return $this
     */
    public function guard(bool $guard)
    {
        $this->_options['guard'] = (bool)$guard;

        return $this;
    }

    /**
     * Set the validation rule set to use.
     *
     * @param string $validate Name of the validation rule set to use.
     * @return $this
     */
    public function validate(string $validate)
    {
        $this->_table->getValidator($validate);
        $this->_options['validate'] = $validate;

        return $this;
    }

    /**
     * Set check existing option.
     *
     * @param bool $checkExisting Guard the properties or not.
     * @return $this
     */
    public function checkExisting(bool $checkExisting)
    {
        $this->_options['checkExisting'] = (bool)$checkExisting;

        return $this;
    }

    /**
     * Option to check the rules.
     *
     * @param bool $checkRules Check the rules or not.
     * @return $this
     */
    public function checkRules(bool $checkRules)
    {
        $this->_options['checkRules'] = (bool)$checkRules;

        return $this;
    }

    /**
     * Sets the atomic option.
     *
     * @param bool $atomic Atomic or not.
     * @return $this
     */
    public function atomic(bool $atomic)
    {
        $this->_options['atomic'] = (bool)$atomic;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->_options;
    }

    /**
     * Setting custom options.
     *
     * @param string $option Option key.
     * @param mixed $value Option value.
     * @return $this
     */
    public function set(string $option, $value)
    {
        if (method_exists($this, $option)) {
            return $this->{$option}($value);
        }
        $this->_options[$option] = $value;

        return $this;
    }
}
