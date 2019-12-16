<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Http\ServerRequest;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\TestSuite\Stub\Response;

/**
 * Dispatches a request capturing the response for integration testing
 * purposes into the Routing\Dispatcher stack.
 *
 * @internal
 */
class LegacyRequestDispatcher
{
    /**
     * @var \Cake\TestSuite\IntegrationTestCase
     */
    protected $_test;

    /**
     * Constructor
     *
     * @param \Cake\TestSuite\IntegrationTestCase $test The test case to run.
     */
    public function __construct($test)
    {
        $this->_test = $test;
    }

    /**
     * Resolve the user provided URL into the actual request URL.
     *
     * @param array|string $url The URL array/string to resolve.
     * @return string
     */
    public function resolveUrl($url)
    {
        return Router::url($url);
    }

    /**
     * Run a request and get the response.
     *
     * @param array $request The request context to execute.
     * @return string|null The generated response.
     */
    public function execute($request)
    {
        $request = new ServerRequest($request);
        $response = new Response();
        $dispatcher = DispatcherFactory::create();
        $dispatcher->getEventManager()->on(
            'Dispatcher.invokeController',
            ['priority' => 999],
            [$this->_test, 'controllerSpy']
        );

        return $dispatcher->dispatch($request, $response);
    }
}
