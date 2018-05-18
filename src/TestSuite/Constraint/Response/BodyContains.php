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
 * BodyContains
 *
 * @internal
 */
class BodyContains extends ResponseBase
{

    /**
     * @var bool
     */
    protected $ignoreCase;

    /**
     * Constructor.
     *
     * @param Response $response Response
     * @param bool $ignoreCase Ignore case
     */
    public function __construct(Response $response, $ignoreCase = false)
    {
        parent::__construct($response);

        $this->ignoreCase = $ignoreCase;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected type
     * @return bool
     */
    public function matches($other)
    {
        $method = 'mb_strpos';
        if ($this->ignoreCase) {
            $method = 'mb_stripos';
        }

        return $method($this->_getBodyAsString(), $other) !== false;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString()
    {
        return 'is in response body';
    }
}
