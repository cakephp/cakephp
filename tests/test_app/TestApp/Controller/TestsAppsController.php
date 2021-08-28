<?php
declare(strict_types=1);

/**
 * TestsAppsController file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestsAppsController
 */
namespace TestApp\Controller;

use RuntimeException;

class TestsAppsController extends AppController
{
    public function index()
    {
        $var = '';
        if ($this->request->getQuery('var')) {
            $var = $this->request->getQuery('var');
        }
        $this->set('var', $var);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function some_method()
    {
        return $this->response->withStringBody('5');
    }

    public function set_action()
    {
        $this->set('var', 'string');
        $this->render('index');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function redirect_to()
    {
        return $this->redirect('http://cakephp.org');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function redirect_to_permanent()
    {
        return $this->redirect('http://cakephp.org', 301);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function set_type()
    {
        return $this->response->withType('json');
    }

    public function throw_exception()
    {
        throw new RuntimeException('Foo');
    }
}
