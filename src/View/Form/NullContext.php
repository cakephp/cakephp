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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Form;

use Cake\Http\ServerRequest;

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
     * @var \Cake\Http\ServerRequest
     */
    protected $_request;

    /**
     * Constructor.
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @param array $context Context info.
     */
    public function __construct(ServerRequest $request, array $context)
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
        return $this->_request->getData($field);
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
