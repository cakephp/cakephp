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
use Cake\Http\Response;
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
     */
    protected ?string $modelClass = 'Posts';

    /**
     * test_request_action method
     */
    public function test_request_action(): Response
    {
        return $this->response->withStringBody('This is a test');
    }

    /**
     * another_ra_test method
     */
    public function another_ra_test(mixed $id, mixed $other): Response
    {
        return $this->response->withStringBody($id + $other);
    }

    /**
     * normal_request_action method
     */
    public function normal_request_action(): Response
    {
        return $this->response->withStringBody('Hello World');
    }

    /**
     * returns $this->here as body
     */
    public function return_here(): Response
    {
        return $this->response->withStringBody($this->here);
    }

    /**
     * post pass, testing post passing
     */
    public function post_pass(): Response
    {
        return $this->response->withStringBody(json_encode($this->request->getData()));
    }

    /**
     * query pass, testing query passing
     */
    public function query_pass(): Response
    {
        return $this->response->withStringBody(json_encode($this->request->getQueryParams()));
    }

    /**
     * cookie pass, testing cookie passing
     */
    public function cookie_pass(): Response
    {
        return $this->response->withStringBody(json_encode($this->request->getCookieParams()));
    }

    /**
     * test param passing and parsing.
     */
    public function params_pass(): Response
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
     */
    public function param_check(): Response
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
     */
    public function session_test(): Response
    {
        return $this->response->withStringBody($this->request->getSession()->read('foo'));
    }

    /**
     * Tests input data transmission
     */
    public function input_test(): Response
    {
        $text = json_decode((string)$this->request->getBody())->hello;

        return $this->response->withStringBody($text);
    }

    /**
     * Tests exception handling
     *
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function error_method(): never
    {
        throw new NotFoundException('Not there or here.');
    }

    /**
     * Tests uploaded files
     */
    public function uploaded_files(): Response
    {
        $files = Hash::flatten($this->request->getUploadedFiles());
        $names = collection($files)->map(fn(UploadedFileInterface $file): ?string => $file->getClientFilename());

        return $this->response->withStringBody(json_encode($names));
    }
}
