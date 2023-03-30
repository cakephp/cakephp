<?php
declare(strict_types=1);

/**
 * CakePHP(tm) Tests <https://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Hash;
use Psr\Http\Message\UploadedFileInterface;
use function Cake\Collection\collection;

/**
 * RequestActionController class
 */
class RequestActionController extends AppController
{
    /**
     * The default model to use.
     *
     * @var string
     */
    protected $modelClass = 'Posts';

    /**
     * test_request_action method
     *
     * @return \Cake\Http\Response
     */
    public function test_request_action()
    {
        return $this->response->withStringBody('This is a test');
    }

    /**
     * another_ra_test method
     *
     * @param mixed $id
     * @param mixed $other
     * @return \Cake\Http\Response
     */
    public function another_ra_test($id, $other)
    {
        return $this->response->withStringBody($id + $other);
    }

    /**
     * normal_request_action method
     *
     * @return \Cake\Http\Response
     */
    public function normal_request_action()
    {
        return $this->response->withStringBody('Hello World');
    }

    /**
     * returns $this->here as body
     *
     * @return \Cake\Http\Response
     */
    public function return_here()
    {
        return $this->response->withStringBody($this->here);
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
     * @return \Cake\Http\Response
     */
    public function post_pass()
    {
        return $this->response->withStringBody(json_encode($this->request->getData()));
    }

    /**
     * query pass, testing query passing
     *
     * @return \Cake\Http\Response
     */
    public function query_pass()
    {
        return $this->response->withStringBody(json_encode($this->request->getQueryParams()));
    }

    /**
     * cookie pass, testing cookie passing
     *
     * @return \Cake\Http\Response
     */
    public function cookie_pass()
    {
        return $this->response->withStringBody(json_encode($this->request->getCookieParams()));
    }

    /**
     * test param passing and parsing.
     *
     * @return \Cake\Http\Response
     */
    public function params_pass()
    {
        return $this->response->withStringBody(json_encode([
            'params' => $this->request->getAttribute('params'),
            'base' => $this->request->getAttribute('base'),
            'here' => $this->request->getRequestTarget(),
            'webroot' => $this->request->getAttribute('webroot'),
            'query' => $this->request->getQueryParams(),
            'url' => $this->request->getUri()->getPath(),
            'contentType' => $this->request->contentType(),
        ]));
    }

    /**
     * param check method.
     *
     * @return \Cake\Http\Response
     */
    public function param_check()
    {
        $this->autoRender = false;
        $content = '';
        if ($this->request->getParam('0')) {
            $content = 'return found';
        }

        return $this->response->withStringBody($content);
    }

    /**
     * Tests session transmission
     *
     * @return \Cake\Http\Response
     */
    public function session_test()
    {
        return $this->response->withStringBody($this->request->getSession()->read('foo'));
    }

    /**
     * Tests input data transmission
     *
     * @return \Cake\Http\Response
     */
    public function input_test()
    {
        $text = json_decode((string)$this->request->getBody())->hello;

        return $this->response->withStringBody($text);
    }

    /**
     * Tests exception handling
     *
     * @throws \Cake\Http\Exception\NotFoundException
     * @return void
     */
    public function error_method()
    {
        throw new NotFoundException('Not there or here.');
    }

    /**
     * Tests uploaded files
     *
     * @return \Cake\Http\Response
     */
    public function uploaded_files()
    {
        $files = Hash::flatten($this->request->getUploadedFiles());
        $names = collection($files)->map(function (UploadedFileInterface $file) {
            return $file->getClientFilename();
        });

        return $this->response->withStringBody(json_encode($names));
    }
}
