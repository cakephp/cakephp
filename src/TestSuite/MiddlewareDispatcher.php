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
use Cake\Http\Server;
use Cake\Http\ServerRequestFactory;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Zend\Diactoros\Stream;

/**
 * Dispatches a request capturing the response for integration
 * testing purposes into the Cake\Http stack.
 *
 * @internal
 */
class MiddlewareDispatcher
{
    /**
     * The test case being run.
     *
     * @var \Cake\TestSuite\IntegrationTestCase
     */
    protected $_test;

    /**
     * The application class name
     *
     * @var string
     */
    protected $_class;

    /**
     * Constructor arguments for your application class.
     *
     * @var array
     */
    protected $_constructorArgs;

    /**
     * Constructor
     *
     * @param \Cake\TestSuite\IntegrationTestCase $test The test case to run.
     * @param string|null $class The application class name. Defaults to App\Application.
     * @param array|null $constructorArgs The constructor arguments for your application class.
     *   Defaults to `['./config']`
     */
    public function __construct($test, $class = null, $constructorArgs = null)
    {
        $this->_test = $test;
        $this->_class = $class ?: Configure::read('App.namespace') . '\Application';
        $this->_constructorArgs = $constructorArgs ?: ['./config'];
    }

    /**
     * Run a request and get the response.
     *
     * @param \Cake\Http\ServerRequest $request The request to execute.
     * @return \Psr\Http\Message\ResponseInterface The generated response.
     */
    public function execute($request)
    {
        try {
            $reflect = new ReflectionClass($this->_class);
            $app = $reflect->newInstanceArgs($this->_constructorArgs);
        } catch (ReflectionException $e) {
            throw new LogicException(sprintf(
                'Cannot load "%s" for use in integration testing.',
                $this->_class
            ));
        }

        // Spy on the controller using the initialize hook instead
        // of the dispatcher hooks as those will be going away one day.
        EventManager::instance()->on(
            'Controller.initialize',
            [$this->_test, 'controllerSpy']
        );

        $server = new Server($app);
        $psrRequest = $this->_createRequest($request);

        return $server->run($psrRequest);
    }

    /**
     * Create a PSR7 request from the request spec.
     *
     * @param array $spec The request spec.
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function _createRequest($spec)
    {
        if (isset($spec['input'])) {
            $spec['post'] = [];
        }
        $request = ServerRequestFactory::fromGlobals(
            array_merge($_SERVER, $spec['environment'], ['REQUEST_URI' => $spec['url']]),
            $spec['query'],
            $spec['post'],
            $spec['cookies']
        );
        $request = $request->withAttribute('session', $spec['session']);

        if (isset($spec['input'])) {
            $stream = new Stream('php://memory', 'rw');
            $stream->write($spec['input']);
            $stream->rewind();
            $request = $request->withBody($stream);
        }

        return $request;
    }
}
