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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Exception;

use Cake\Core\Exception\CakeException;

/**
 * Parent class for all the HTTP related exceptions in CakePHP.
 * All HTTP status/error related exceptions should extend this class so
 * catch blocks can be specifically typed.
 *
 * You may also use this as a meaningful bridge to {@link \Cake\Core\Exception\CakeException}, e.g.:
 * throw new \Cake\Network\Exception\HttpException('HTTP Version Not Supported', 505);
 */
class HttpException extends CakeException
{
    /**
     * @inheritDoc
     */
    protected int $_defaultCode = 500;

    /**
     * @var array<string, mixed>
     */
    protected array $headers = [];

    /**
     * Set a single HTTP response header.
     *
     * @param string $header Header name
     * @param list<string>|string|null $value Header value
     * @return void
     */
    public function setHeader(string $header, array|string|null $value = null): void
    {
        $this->headers[$header] = $value;
    }

    /**
     * Sets HTTP response headers.
     *
     * @param array<string, mixed> $headers Array of header name and value pairs.
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Returns array of response headers.
     *
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
