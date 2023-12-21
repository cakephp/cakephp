<?php
declare(strict_types=1);

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

use Cake\Event\EventInterface;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\RedirectException;
use OutOfBoundsException;
use RuntimeException;

/**
 * PostsController class
 */
class PostsController extends AppController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->loadComponent('Flash');
        $this->loadComponent('RequestHandler');
        $this->loadComponent('FormProtection');

        $this->middleware(function ($request, $handler) {
            return $handler->handle($request->withAttribute('for-all', true));
        });
        $this->middleware(function ($request, $handler) {
            return $handler->handle($request->withAttribute('index-only', true));
        }, ['only' => 'index']);
        $this->middleware(function ($request, $handler) {
            return $handler->handle($request->withAttribute('all-except-index', true));
        }, ['except' => ['index']]);
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
        if ($this->request->getParam('action') !== 'securePost') {
            $this->getEventManager()->off($this->FormProtection);
        }

        $this->FormProtection->setConfig('unlockedFields', ['some_unlocked_field']);
    }

    public function beforeRender(EventInterface $event)
    {
        if ($this->request->getQuery('clear')) {
            $this->set('flash', $this->request->getSession()->consume('Flash'));
        }
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
     * @return \Cake\Http\Response|null
     */
    public function someRedirect()
    {
        $this->Flash->success('A success message');

        return $this->redirect('/somewhere');
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
        $this->viewBuilder()->setOption('serialize', ['data']);
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

    /**
     * @return \Cake\Http\Response
     */
    public function file()
    {
        $filename = $this->request->getQuery('file');
        if ($filename) {
            $path = TMP . $filename;

            return $this->response->withFile($path, ['download' => true])
                ->withHeader('Content-Disposition', "attachment;filename=*UTF-8''{$filename}");
        }

        return $this->response->withFile(__FILE__);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function header()
    {
        return $this->getResponse()->withHeader('X-Cake', 'custom header');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function hostData()
    {
        $data = [
            'host' => $this->request->host(),
            'isSsl' => $this->request->is('https'),
        ];

        return $this->getResponse()->withStringBody(json_encode($data));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function empty_response()
    {
        return $this->getResponse()->withStringBody('');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function secretCookie()
    {
        return $this->response
            ->withCookie(new Cookie('secrets', 'name'))
            ->withStringBody('ok');
    }

    public function redirectWithCookie()
    {
        $cookies = [
            Cookie::create('remember', '1'),
            Cookie::create('expired', '')->withExpired(),
        ];
        $values = [];
        foreach ($cookies as $cookie) {
            $values[] = $cookie->toHeaderValue();
        }
        $headers = ['Set-Cookie' => $values];

        throw new RedirectException('/posts', 302, $headers);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function stacked_flash()
    {
        $this->Flash->error('Error 1');
        $this->Flash->error('Error 2');
        $this->Flash->success('Success 1', ['key' => 'custom']);
        $this->Flash->success('Success 2', ['key' => 'custom']);

        return $this->getResponse()->withStringBody('');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function throw_exception()
    {
        $this->Flash->error('Error 1');
        throw new OutOfBoundsException('oh no!');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function throw_chained()
    {
        $inner = new RuntimeException('inner badness');
        throw new OutOfBoundsException('oh no!', 1, $inner);
    }
}
