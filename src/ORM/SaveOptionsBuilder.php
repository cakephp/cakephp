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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use ArrayAccess;
use ArrayObject;
use RuntimeException;
use Cake\ORM\AssociationsNormalizerTrait;

/**
 * OOP style Save Option Builder.
 *
 * This allows you to build options to save entities in a OOP style and helps
 * you to avoid mistakes by validating the options as you build them.
 *
 * @see \Cake\Datasource\RulesChecker
 */
class SaveOptionsBuilder extends ArrayObject implements ArrayAccess
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
     * @var \Cake\ORM\Table;
     */
    protected $_table;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Table $table A table instance.
     */
    public function __construct(Table $table, array $options = [])
    {
        $this->_table = $table;
        $this->parseArray($options);
    }

    /**
     * Takes an options array and populates the option object with the data.
     *
     * @param array $array Options array
     * @return \Cake\ORM\SaveOptionsBuilder
     */
    public function parseArray($array)
    {
        foreach ($array as $key => $value) {
            if (method_exists($this, $key)) {
                $this->{$key}($value);
            }
        }
        return $this;
    }

    /**
     * Set associated options.
     *
     * @param string|array $associated String or array of associations.
     * @return \Cake\ORM\SaveOptionsBuilder
     */
    public function associated($associated)
    {
        $associated = $this->_normalizeAssociations($associated);
        $this->_associated($this->_table, $associated);
        $this->_options['associated'] = $associated;

        return $this;
    }

    protected function _associated(Table $table, $associations)
    {
        foreach ($associations as $key => $associated) {
            if (is_int($key)) {
                $this->_checkAssociation($table, $associated);
                continue;
            }
            $this->_checkAssociation($table, $key);
            if (isset($associated['associated'])) {
                $this->_associated($table->association($key)->target(), $associated['associated']);
                continue;
            }
        }
    }

    protected function _checkAssociation(Table $table, $association)
    {
        if (!$table->associations()->has($association)) {
            throw new RuntimeException(sprintf('Table `%s` is not associated with `%s`', get_class($table), $association));
        }
    }

    /**
     * Set the guard option.
     *
     * @param bool $guard Guard the properties or not.
     * @return \Cake\ORM\SaveOptionsBuilder
     */
    public function guard($guard)
    {
        $this->_options['guard'] = (bool)$guard;
        return $this;
    }

    /**
     * Set the validation rule set to use.
     *
     * @param string $validate Name of the validation rule set to use.
     * @return \Cake\ORM\SaveOptionsBuilder
     */
    public function validate($validate)
    {
        $this->_table->validator($validate);
        $this->_options['validate'] = $validate;
        return $this;
    }

    /**
     * Set check existing option.
     *
     * @param bool $checkExisting Guard the properties or not.
     * @return \Cake\ORM\SaveOptionsBuilder
     */
    public function checkExisting($checkExisting)
    {
        $this->_options['checkExisting'] = (bool)$checkExisting;
        return $this;
    }

    /**
     * Option to check the rules.
     *
     * @param bool $checkRules Check the rules or not.
     * @return \Cake\ORM\SaveOptionsBuilder
     */
    public function checkRules($checkRules)
    {
        $this->_options['checkRules'] = (bool)$checkRules;
        return $this;
    }

    /**
     * Sets the atomic option.
     *
     * @param bool $atomic Atomic or not.
     * @return \Cake\ORM\SaveOptionsBuilder
     */
    public function atomic($atomic)
    {
        $this->_options['atomic'] = (bool)$atomic;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->_options;
    }

    /**
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->_options[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->_options[$offset];
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset}($value);
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->_options[$offset]);
    }
}
