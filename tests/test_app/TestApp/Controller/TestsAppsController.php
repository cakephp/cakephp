<?php
/**
 * TestsAppsController file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class TestsAppsController
 *
 */
namespace TestApp\Controller;

class TestsAppsController extends AppController
{
    public $components = ['RequestHandler'];

    public function index()
    {
        $var = '';
        if (isset($this->request->query['var'])) {
            $var = $this->request->query['var'];
        }
        $this->set('var', $var);
    }

    public function some_method()
    {
        $this->response->body(5);
        return $this->response;
    }

    public function set_action()
    {
        $this->set('var', 'string');
        $this->render('index');
    }

    public function redirect_to()
    {
        return $this->redirect('http://cakephp.org');
    }

    public function redirect_to_permanent()
    {
        return $this->redirect('http://cakephp.org', 301);
    }

    public function set_type()
    {
        $this->response->type('json');
        return $this->response;
    }

    public function throw_exception()
    {
        throw new \RuntimeException('Foo');
    }
}
