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
 * Provides common functionality for ContextInterface implementations.
 */
abstract class AbstractContext implements ContextInterface
{
    /**
     * The request object.
     *
     * @var \Cake\Network\Request
     */
    protected $_request;

    /**
     * The request type of the attached form.
     *
     * @var string
     */
    protected $_requestType;

    /**
     * Constructor.
     *
     * @param \Cake\Network\Request $request The request object.
     * @param string $requestType The type of request used by the form this context is attached to.
     */
    protected function __construct(Request $request, $requestType)
    {
        $this->_request = $request;
        if ($requestType === null || $requestType === 'file') {
            $this->_requestType = $this->isCreate() ? 'post' : 'put';
        } else {
            $this->_requestType = $requestType;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function val($field)
    {
        if ($this->_requestType === 'get') {
            return $this->_request->query($field);
        }
        return $this->_request->data($field);
    }
}
