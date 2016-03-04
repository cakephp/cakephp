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

/**
 * Provides a context provider that does nothing.
 *
 * This context provider simply fulfils the interface requirements
 * that FormHelper has and allows access to the request data.
 */
class NullContext extends AbstractContext
{

    /**
     * Constructor.
     *
     * @param \Cake\Network\Request $request The request object.
     * @param array $context Context info.
     * @param string $requestType The type of request used by the form this context is attached to.
     */
    public function __construct(Request $request, array $context, $requestType)
    {
        parent::__construct($request, $requestType);
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
