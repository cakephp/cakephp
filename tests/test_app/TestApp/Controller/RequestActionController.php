<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\Network\Exception\NotFoundException;

/**
 * RequestActionController class
 *
 */
class RequestActionController extends AppController
{

    /**
     * The default model to use.
     *
     * @var string
     */
    public $modelClass = 'Posts';

    /**
     * test_request_action method
     *
     * @return \Cake\Network\Response
     */
    public function test_request_action()
    {
        $this->response->body('This is a test');
        return $this->response;
    }

    /**
     * another_ra_test method
     *
     * @param mixed $id
     * @param mixed $other
     * @return \Cake\Network\Response
     */
    public function another_ra_test($id, $other)
    {
        $this->response->body($id + $other);
        return $this->response;
    }

    /**
     * normal_request_action method
     *
     * @return \Cake\Network\Response
     */
    public function normal_request_action()
    {
        $this->response->body('Hello World');
        return $this->response;
    }

    /**
     * returns $this->here as body
     *
     * @return \Cake\Network\Response
     */
    public function return_here()
    {
        $this->response->body($this->here);
        return $this->response;
    }

    /**
     * paginate_request_action method
     *
     * @return void
     */
    public function paginate_request_action()
    {
        $data = $this->paginate();
        $this->autoRender = false;
    }

    /**
     * post pass, testing post passing
     *
     * @return \Cake\Network\Response
     */
    public function post_pass()
    {
        $this->response->body(json_encode($this->request->data));
        return $this->response;
    }

    /**
     * query pass, testing query passing
     *
     * @return \Cake\Network\Response
     */
    public function query_pass()
    {
        $this->response->body(json_encode($this->request->query));
        return $this->response;
    }

    /**
     * cookie pass, testing cookie passing
     *
     * @return \Cake\Network\Response
     */
    public function cookie_pass()
    {
        $this->response->body(json_encode($this->request->cookies));
        return $this->response;
    }

    /**
     * test param passing and parsing.
     *
     * @return \Cake\Network\Response
     */
    public function params_pass()
    {
        $this->response->body(json_encode([
            'params' => $this->request->params,
            'base' => $this->request->base,
            'here' => $this->request->here,
            'webroot' => $this->request->webroot,
            'params' => $this->request->params,
            'query' => $this->request->query,
            'url' => $this->request->url,
            'contentType' => $this->request->contentType(),
        ]));
        return $this->response;
    }

    /**
     * param check method.
     *
     * @return \Cake\Network\Response
     */
    public function param_check()
    {
        $this->autoRender = false;
        $content = '';
        if (isset($this->request->params[0])) {
            $content = 'return found';
        }
        $this->response->body($content);
        return $this->response;
    }

    /**
     * Tests session transmission
     *
     * @return \Cake\Network\Response
     */
    public function session_test()
    {
        $this->response->body($this->request->session()->read('foo'));
        return $this->response;
    }

    /**
     * Tests input data transmission
     *
     * @return \Cake\Network\Response
     */
    public function input_test()
    {
        $this->response->body($this->request->input('json_decode')->hello);
        return $this->response;
    }

    /**
     * Tests exception handling
     *
     * @throws \Cake\Network\Exception\NotFoundException
     * @return void
     */
    public function error_method()
    {
        throw new NotFoundException('Not there or here.');
    }
}
