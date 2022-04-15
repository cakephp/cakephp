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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error\Renderer;

use Cake\Http\ServerRequest;
use Throwable;

/**
 * Selects between the Html and Console renderers based on the current PHP Sapi
 *
 * Used as the default exception renderer in 4.4.0+
 *
 * @todo 5.0 Implement \Cake\Error\ExceptionRendererInterface. This implementation can't implement
 *  the concrete interface because the return types are not compatible.
 */
class SapiBasedExceptionRenderer
{
    /**
     * @var \Cake\Error\ExceptionRendererInterface|null
     */
    protected $renderer;

    /**
     * The exception being handled.
     *
     * @var \Throwable
     */
    protected $error;

    /**
     * If set, this will be request used to create the controller that will render
     * the error.
     *
     * @var \Cake\Http\ServerRequest|null
     */
    protected $request;

    /**
     * @var array
     */
    protected $config;

    /**
     * Creates the controller to perform rendering on the error response.
     *
     * @param \Throwable $exception Exception.
     * @param \Cake\Http\ServerRequest|null $request The request if this is set it will be used
     *   instead of creating a new one.
     * @param array $config Error handling configuration
     */
    public function __construct(Throwable $exception, ?ServerRequest $request = null, array $config = [])
    {
        $this->error = $exception;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * Choose a renderer based on the PHP SAPI
     *
     * @return class-string<\Cake\Error\ExceptionRendererInterface>
     */
    protected function chooseRenderer(): string
    {
        return PHP_SAPI === 'cli' ? ConsoleExceptionRenderer::class : HtmlExceptionRenderer::class;
    }

    /**
     * Create or re-use the renderer chosen based on the current SAPI
     *
     * @return \Cake\Error\ExceptionRendererInterface
     */
    protected function makeRenderer()
    {
        if (!$this->renderer) {
            $className = $this->chooseRenderer();
            $this->renderer = new $className($this->error, $this->request, $this->config);
        }

        return $this->renderer;
    }

    /**
     * Render an exception based on the selected renderer.
     *
     * @return \Cake\Http\Response|string
     */
    public function render()
    {
        return $this->makeRenderer()->render();
    }

    /**
     * Write output to the selected renderer
     *
     * @param \Psr\Http\Message\ResponseInterface|string $output The output to print.
     * @return void
     */
    public function write($output): void
    {
        $this->makeRenderer()->write($output);
    }
}
