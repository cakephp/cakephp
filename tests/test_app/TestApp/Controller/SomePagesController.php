<?php
declare(strict_types=1);

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
     * @return void
     */
    public function display(mixed $page = null)
    {
    }

    /**
     * index method
     */
    public function index(): bool
    {
        return true;
    }

    /**
     * Test method for returning responses.
     */
    public function responseGenerator(): Response
    {
        return $this->response->withStringBody('new response');
    }

    protected function _fail()
    {
    }
}
