<?php
declare(strict_types=1);

namespace TestApp\Error;

use Cake\Error\ErrorHandler;

/**
 * Testing stub.
 */
class TestErrorHandler extends ErrorHandler
{
    /**
     * Access the response used.
     *
     * @var \Cake\Http\Response
     */
    public $response;

    /**
     * Stub output clearing in tests.
     *
     * @return void
     */
    protected function _clearOutput(): void
    {
        // noop
    }

    /**
     * Stub sending responses
     *
     * @param \Cake\Http\Response $response
     * @return void
     */
    protected function _sendResponse($response): void
    {
        $this->response = $response;
    }
}
