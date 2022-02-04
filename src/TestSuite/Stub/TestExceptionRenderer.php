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
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Stub;

use Cake\Error\ExceptionRendererInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Test Exception Renderer.
 *
 * Use this class if you want to re-throw exceptions that would otherwise be
 * caught by the ErrorHandlerMiddleware. This is useful while debugging or
 * writing integration test cases.
 *
 * @see \Cake\TestSuite\IntegrationTestCase::disableErrorHandlerMiddleware()
 * @internal
 */
class TestExceptionRenderer implements ExceptionRendererInterface
{
    /**
     * Simply rethrow the given exception
     *
     * @param \Throwable $exception Exception.
     * @return void
     * @throws \Throwable $exception Rethrows the passed exception.
     */
    public function __construct(Throwable $exception)
    {
        throw $exception;
    }

    /**
     * @inheritDoc
     */
    public function render(): ResponseInterface
    {
        throw new LogicException('You cannot use this class to render exceptions.');
    }

    /**
     * Part of upcoming interface requirements
     *
     * @param \Psr\Http\Message\ResponseInterface|string $output The output or response to send.
     * @return void
     */
    public function write($output): void
    {
    }
}
