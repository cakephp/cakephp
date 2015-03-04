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
namespace Cake\Form;

use Cake\Form\Form;
use Cake\Network\Request;

/**
 * PersitForm allows for errors and data on site-wide forms
 * to be persisted after redirects.
 */
class PersistForm extends Form
{

    /**
     * Request object
     *
     * @var \Cake\Network\Request
     */
    protected $_request;

    /**
     * Session key to store persist data
     *
     * @var string
     */
    protected $_persistKey;

    /**
     * Initializes a new instance
     *
     * @param \Cake\Network\Request $request
     */
    public function __construct(Request $request)
    {
        $this->_request = $request;
        $this->_persistKey = sprintf('Form.%s', __CLASS__);

        $session = $this->_request->session();
        if ($session->check($this->_persistKey)) {
            $this->_errors = $session->read($this->_persistKey . '.errors');
            $request->data = $session->read($this->_persistKey . '.data');
            $session->delete($this->_persistKey);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $data)
    {
        $return = parent::execute($data);
        if (!$return) {
            $this->_persist();
        }
        return $return;
    }

    /**
     * Writes form errors and data to session
     *
     * @return void
     */
    protected function _persist()
    {
         $this->_request->session()->write($this->_persistKey, [
            'errors' => $this->_errors,
            'data' => $this->_request->data,
        ]);
    }
}
