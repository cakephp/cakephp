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
namespace TestApp\Controller;

use Cake\Event\Event;
use Cake\Http\Cookie\Cookie;

/**
 * PostsController class
 */
class PostsController extends AppController
{
    /**
     * Components array
     *
     * @var array
     */
    public $components = [
        'Flash',
        'RequestHandler' => [
            'enableBeforeRedirect' => false,
        ],
        'Security',
    ];

    /**
     * beforeFilter
     *
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        if ($this->request->getParam('action') !== 'securePost') {
            $this->getEventManager()->off($this->Security);
        }

        $this->Security->setConfig('unlockedFields', ['some_unlocked_field']);
    }

    /**
     * Index method.
     *
     * @param string $layout
     * @return void
     */
    public function index($layout = 'default')
    {
        $this->Flash->error('An error message');
        $this->response = $this->response->withCookie(new Cookie('remember_me', 1));
        $this->set('test', 'value');
        $this->viewBuilder()->setLayout($layout);
    }

    /**
     * Sets a flash message and redirects (no rendering)
     *
     * @return \Cake\Http\Response
     */
    public function flashNoRender()
    {
        $this->Flash->error('An error message');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Stub get method
     *
     * @return void
     */
    public function get()
    {
        // Do nothing.
    }

    /**
     * Stub AJAX method
     *
     * @return void
     */
    public function ajax()
    {
        $data = [];

        $this->set(compact('data'));
        $this->set('_serialize', ['data']);
    }

    /**
     * Post endpoint for integration testing with security component.
     *
     * @return void
     */
    public function securePost()
    {
        return $this->response->withStringBody('Request was accepted');
    }

    public function file()
    {
        return $this->response->withFile(__FILE__);
    }

    public function header()
    {
        return $this->getResponse()->withHeader('X-Cake', 'custom header');
    }

    public function hostData()
    {
        $data = [
            'host' => $this->request->host(),
            'isSsl' => $this->request->is('ssl'),
        ];

        return $this->getResponse()->withStringBody(json_encode($data));
    }

    public function empty_response()
    {
        return $this->getResponse()->withStringBody('');
    }

    public function stacked_flash()
    {
        $this->Flash->error('Error 1');
        $this->Flash->error('Error 2');
        $this->Flash->success('Success 1', ['key' => 'custom']);
        $this->Flash->success('Success 2', ['key' => 'custom']);

        return $this->getResponse()->withStringBody('');
    }

    public function throw_exception()
    {
        $this->Flash->error('Error 1');
        throw new \OutOfBoundsException('oh no!');
    }
}
