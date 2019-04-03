<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;

/**
 * SomePagesController class
 */
class SomePagesController extends Controller
{

    /**
     * display method
     *
     * @param mixed $page
     * @return void
     */
    public function display($page = null)
    {
    }

    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        return true;
    }

    /**
     * Test method for returning responses.
     *
     * @return \Cake\Http\Response
     */
    public function responseGenerator()
    {
        $this->response->body('new response');

        return $this->response;
    }

    protected function _fail()
    {
    }
}
