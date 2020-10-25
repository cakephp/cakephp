<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 *
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
namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * RequestHandlerTestController class
 */
class RequestHandlerTestController extends Controller
{
    /**
     * test method for AJAX redirection
     *
     * @return void
     */
    public function destination()
    {
        $this->viewBuilder()->setTemplatePath('Posts');
        $this->render('index');
    }

    /**
     * test method for AJAX redirection + parameter parsing
     *
     * @param string|null $one
     * @param string|null $two
     * @return void
     */
    public function param_method($one = null, $two = null)
    {
        echo "one: $one two: $two";
        $this->autoRender = false;
    }

    /**
     * test method for testing layout rendering when isAjax()
     *
     * @return void
     */
    public function ajax2_layout()
    {
        $this->viewBuilder()->setLayout('ajax2');
        $this->destination();
    }

    /**
     * test method for testing that response type set in action doesn't get
     * overridden by RequestHandlerComponent::beforeRender()
     *
     * @return void
     */
    public function set_response_type()
    {
        $this->response = $this->response->withType('txt');
    }
}
