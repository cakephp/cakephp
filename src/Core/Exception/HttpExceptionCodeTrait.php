<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core\Exception;

/**
 * Handles HTTP exception headers for exceptions that implement HttpExceptionCodeInterface.
 */
trait HttpExceptionCodeTrait
{
    /**
     * @var array
     */
    protected $responseHeaders = [];

    /**
     * @inheritDoc
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * @inheritDoc
     */
    public function addResponseHeaders(array $headers): void
    {
        foreach ($headers as $key => $value) {
            if (array_key_exists($key, $this->responseHeaders)) {
                array_push($this->responseHeaders[$key], ...(array)$value);
            } else {
                $this->responseHeaders[$key] = (array)$value;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function dropResponseHeader(string $header): void
    {
        unset($this->responseHeaders[$header]);
    }
}
