<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 *
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
namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * RequestHandlerTestController class
 *
 */
class RequestHandlerTestController extends Controller
{

    /**
     * test method for ajax redirection
     *
     * @return void
     */
    public function destination()
    {
        $this->viewBuilder()->templatePath('Posts');
        $this->render('index');
    }

    /**
     * test method for ajax redirection + parameter parsing
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
        $this->viewBuilder()->layout('ajax2');
        $this->destination();
    }
}
