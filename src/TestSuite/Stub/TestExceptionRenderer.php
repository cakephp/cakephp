<?php
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

use Exception;

/**
 * Test Exception Renderer.
 *
 * You can use this class if you want to re-throw exceptions that else would
 * be caught by the ErrorHandlerMiddleware.
 * This is useful while debugging or writitng integration test cases.
 *
 * ```
 * use Cake\Core\Configure;
 * use Cake\TestSuite\Stub\TestExceptionRenderer;
 *
 * Configure::write('Error.exceptionRenderer', TestExceptionRenderer::class);
 * ```
 *
 * @see \Cake\TestSuite\IntegrationTestCase::disableErrorHandlerMiddleware()
 */
class TestExceptionRenderer
{

    /**
     * Simply rethrows the given exception
     *
     * @param \Exception $exception Exception.
     * @return void
     * @throws \Exception $exception Rethrows the passed exception.
     */
    public function __construct(Exception $exception)
    {
        throw $exception;
    }
}
