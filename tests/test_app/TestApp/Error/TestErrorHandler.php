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
     * Stub sending responses
     *
     * @param \Cake\Http\Response $response
     */
    protected function _sendResponse($response): void
    {
        $this->response = $response;
    }
}
