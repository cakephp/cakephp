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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Factory method for building controllers from request/response pairs.
 *
 * @template TController
 */
interface ControllerFactoryInterface
{
    /**
     * Create a controller for a given request
     *
     * @param \Cake\Http\ServerRequest $request The request to build a controller for.
     * @return mixed
     * @throws \Cake\Http\Exception\MissingControllerException
     * @psalm-return TController
     */
    public function create(ServerRequest $request);

    /**
     * Invoke a controller's action and wrapping methods.
     *
     * @param mixed $controller The controller to invoke.
     * @return \Psr\Http\Message\ResponseInterface The response
     * @psalm-param TController $controller
     */
    public function invoke($controller): ResponseInterface;
}
