<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Routing\Router;

/**
 * AuthTestController class
 */
class AuthTestController extends Controller
{

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Auth'];

    /**
     * testUrl property
     *
     * @var string|array
     */
    public $testUrl = null;

    /**
     * construct method
     *
     * @param \Cake\Http\ServerRequest|null $request Request object for this controller. Can be null for testing,
     *   but expect that features that use the request parameters will not work.
     * @param \Cake\Http\Response|null $response Response object for this controller.
     */
    public function __construct($request = null, $response = null)
    {
        $request->addParams(Router::parse('/auth_test'));
        $request->here = '/auth_test';
        $request->webroot = '/';
        Router::setRequestInfo($request);
        parent::__construct($request, $response);
    }

    /**
     * login method
     *
     * @return void
     */
    public function login()
    {
    }

    /**
     * logout method
     *
     * @return void
     */
    public function logout()
    {
    }

    /**
     * add method
     *
     * @return void
     */
    public function add()
    {
        echo 'add';
    }

    /**
     * view method
     *
     * @return void
     */
    public function view()
    {
        echo 'view';
    }

    /**
     * add method
     *
     * @return void
     */
    public function camelCase()
    {
        echo 'camelCase';
    }

    /**
     * redirect method
     *
     * @param string|array $url
     * @param int $status
     * @return \Cake\Http\Response|null
     */
    public function redirect($url, $status = null)
    {
        $this->testUrl = Router::url($url);

        return parent::redirect($url, $status);
    }

    /**
     * isAuthorized method
     *
     * @return void
     */
    public function isAuthorized()
    {
    }
}
