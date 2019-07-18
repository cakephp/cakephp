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
 * HeaderSet
 *
 * @internal
 */
class HeaderSet extends ResponseBase
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
        return $this->response->hasHeader($this->headerName);
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString()
    {
        return sprintf('response has header \'%s\'', $this->headerName);
    }

    /**
     * Overwrites the descriptions so we can remove the automatic "expected" message
     *
     * @param mixed $other Value
     * @return string
     */
    protected function failureDescription($other)
    {
        return $this->toString();
    }
}
