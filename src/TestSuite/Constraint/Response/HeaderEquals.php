<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Response;

use Cake\Http\Response;

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
    protected $headerName;

    /**
     * Constructor.
     *
     * @param Response $response Response
     * @param string $headerName Header name
     */
    public function __construct($response, $headerName)
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
    public function matches($other)
    {
        return $this->response->getHeaderLine($this->headerName) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString()
    {
        $responseHeader = $this->response->getHeaderLine($this->headerName);

        return sprintf('equals content in header \'%s\' (`%s`)', $this->headerName, $responseHeader);
    }
}
