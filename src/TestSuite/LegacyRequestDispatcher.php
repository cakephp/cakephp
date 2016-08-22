<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Routing\DispatcherFactory;
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
     * Constructor
     *
     * @param \Cake\TestSuite\IntegrationTestCase $test The test case to run.
     */
    public function __construct($test)
    {
        $this->_test = $test;
    }

    /**
     * Run a request and get the response.
     *
     * @param array $request The request context to execute.
     * @return \Cake\Network\Response The generated response.
     */
    public function execute($request)
    {
        $request = new Request($request);
        $response = new Response();
        $dispatcher = DispatcherFactory::create();
        $dispatcher->eventManager()->on(
            'Dispatcher.invokeController',
            ['priority' => 999],
            [$this->_test, 'controllerSpy']
        );
        $dispatcher->dispatch($request, $response);

        return $response;
    }
}
