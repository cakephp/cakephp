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
 * Indicates that the exception code should be returned as an HTTP code.
 */
interface HttpExceptionCodeInterface
{
    /**
     * @return array
     */
    public function getResponseHeaders(): array;

    /**
     * @param array $headers Array of header name and value pairs.
     * @return void
     */
    public function addResponseHeaders(array $headers): void;

    /**
     * @param string $header Name of header to drop from response
     * @return void
     */
    public function dropResponseHeader(string $header): void;
}
