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
namespace Cake\View\Form;

use Cake\Network\Request;
use Cake\View\Form\ContextInterface;

/**
 * Provides a context provider that does nothing.
 *
 * This context provider simply fulfils the interface requirements
 * that FormHelper has and allows access to the request data.
 */
class NullContext implements ContextInterface
{

    /**
     * The request object.
     *
     * @var \Cake\Network\Request
     */
    protected $_request;

    /**
     * Constructor.
     *
     * @param \Cake\Network\Request $request The request object.
     * @param array $context Context info.
     */
    public function __construct(Request $request, array $context)
    {
        $this->_request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function primaryKey()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function isPrimaryKey($field)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isCreate()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function val($field)
    {
        return $this->_request->data($field);
    }

    /**
     * {@inheritDoc}
     */
    public function isRequired($field)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function fieldNames()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function type($field)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function attributes($field)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function hasError($field)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function error($field)
    {
        return [];
    }
}
