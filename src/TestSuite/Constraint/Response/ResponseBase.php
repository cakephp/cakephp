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

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Base constraint for response constraints
 *
 * @internal
 */
abstract class ResponseBase extends Constraint
{

    /**
     * @var \Cake\Http\Response
     */
    protected $response;

    /**
     * Constructor
     *
     * @param \Cake\Http\Response $response Response
     */
    public function __construct($response)
    {
        parent::__construct();

        if (!$response) {
            throw new AssertionFailedError('No response set, cannot assert content.');
        }

        $this->response = $response;
    }

    /**
     * Get the response body as string
     *
     * @return string The response body.
     */
    protected function _getBodyAsString()
    {
        return (string)$this->response->getBody();
    }
}
