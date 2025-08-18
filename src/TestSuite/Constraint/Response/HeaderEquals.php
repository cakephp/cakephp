<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.7.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Response;

use Psr\Http\Message\ResponseInterface;

/**
 * HeaderEquals
 *
 * @internal
 */
class HeaderEquals extends ResponseBase
{
    /**
     * @var string
     */
    protected string $headerName;

    /**
     * Constructor.
     *
     * @param \Psr\Http\Message\ResponseInterface $response A response instance.
     * @param string $headerName Header name
     */
    public function __construct(ResponseInterface $response, string $headerName)
    {
        parent::__construct($response);

        $this->headerName = $headerName;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        return $this->response->getHeaderLine($this->headerName) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        $responseHeader = $this->response->getHeaderLine($this->headerName);

        return sprintf("equals content in header '%s' (`%s`)", $this->headerName, $responseHeader);
    }
}
