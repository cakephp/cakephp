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
use Cake\Event\EventManager;
use Cake\Http\ResponseTransformer;
use Cake\Http\Server;
use Cake\Http\ServerRequestFactory;
use LogicException;
use ReflectionClass;
use ReflectionException;

/**
 * Dispatches a request capturing the response for integration
 * testing purposes into the Cake\Http stack.
 *
 * @internal
 */
class MiddlewareDispatcher
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
     * @param \Cake\Network\Request $request The request to execute.
     * @return \Cake\Network\Response The generated response.
     */
    public function execute($request)
    {
        try {
            $namespace = Configure::read('App.namespace');
            $reflect = new ReflectionClass($namespace . '\Application');
            $app = $reflect->newInstance('./config');
        } catch (ReflectionException $e) {
            throw new LogicException(sprintf(
                'Cannot load "%s" for use in integration testing.',
                $class
            ));
        }

        // Spy on the controller using the initialize hook instead
        // of the dispatcher hooks as those will be going away one day.
        EventManager::instance()->on(
            'Controller.initialize',
            [$this->_test, 'controllerSpy']
        );

        $server = new Server($app);
        $psrRequest = ServerRequestFactory::fromGlobals(
            array_merge($_SERVER, $request['environment'], ['REQUEST_URI' => $request['url']]),
            $request['query'],
            $request['post'],
            $request['cookies']
        );
        $psrRequest = $psrRequest->withAttribute('session', $request['session']);
        $response = $server->run($psrRequest);
        return ResponseTransformer::toCake($response);
    }
}
